<?php
/**
  * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
  *
  * This file is a part of Codendi.
  *
  * Codendi is free software; you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation; either version 2 of the License, or
  * (at your option) any later version.
  *
  * Codendi is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Codendi. If not, see <http://www.gnu.org/licenses/
  */
require_once('common/backend/Backend.class.php');
require_once('GitDao.class.php');
require_once('GitDriver.class.php');
require_once('Git_Backend_Interface.php');
require_once('GitRepository.class.php');
require_once('exceptions/GitBackendException.class.php');

/**
 * Description of GitBackend
 *
 * @author Guillaume Storchi
 */
class GitBackend extends Backend implements Git_Backend_Interface {
    
    private $driver;    
    private $packagesFile;
    private $configFile;
    //path MUST end with a '/'
    private $gitRootPath;

    const DEFAULT_DIR_MODE = '770';    

    protected function __construct() {
        $this->gitRootPath  = '';
        $this->driver       = new GitDriver();
        $this->packagesFile = 'etc/packages.ini';
        $this->configFile   = 'etc/config.ini';
        $this->dao          = new GitDao();
        //WARN : this is much safer to set it to an absolute path
        $this->gitRootPath  = Git_Backend_Interface::GIT_ROOT_PATH ;
        $this->gitBackupDir = PluginManager::instance()->getPluginByName('git')->getPluginInfo()->getPropVal('git_backup_dir');        
    }

    public function setGitRootPath($gitRootPath) {
        $this->gitRootPath = $gitRootPath;
    }

    public function getGitRootPath() {
        return $this->gitRootPath;
    }   

    public function getDao() {
        return $this->dao;
    }
    public function getDriver() {
        return $this->driver;
    }
    
    public function createFork($clone) {
        if ( $clone->exists() ) {
           throw new GitBackendException('Repository already exists');
        }        
        $parentPath  = $clone->getParent()->getPath();
        $parentPath  = $this->getGitRootPath().DIRECTORY_SEPARATOR.$parentPath;
        $forkPath    = $clone->getPath();
        $forkPath    = $this->getGitRootPath().DIRECTORY_SEPARATOR.$forkPath;        
        $this->getDriver()->fork($parentPath, $forkPath);

        return $this->setUpRepository($clone);
    }

    /**
     * Function that setup a repository , each repository has repo/ directory and forks/ directory
     * @param <type> $rootPath
     * @param <type> $mode
     * @return <type>
     * @todo move gitroopath creation to an install script
     */
    public function createReference($repository) {        
        if ( $repository->exists() ) {
             throw new GitBackendException('Repository already exists');
        }        
        $path = $repository->getPath();
        //create git root if does not exist
        $this->createGitRoot();
        //create project dir if does not exists
        $this->createProjectRoot($repository);
        $path = $this->getGitRootPath().DIRECTORY_SEPARATOR.$path;
        mkdir($path, 0770, true);
        chdir($path);
        $this->getDriver()->init($bare=true);

        return $this->setUpRepository($repository);
    }

    /**
     * Once the repository is created/forked, set it up with proper configuration.
     *
     * @param GitRepository $repository The repository
     *
     * @return Boolean
     */
    public function setUpRepository($repository) {
        $path = $this->getGitRootPath().DIRECTORY_SEPARATOR.$repository->getPath();
        $this->getDriver()->activateHook('post-update', $path);
        $this->deployPostReceive($path);
        $id = $this->getDao()->save($repository);
        $repository->setId($id);
        $this->setUpMailingHook($repository);
        $this->changeRepositoryAccess($repository);
        $this->setRepositoryPermissions($repository);
        return true;
    }

    public function delete($repository, $ignoreHasChildren = false) {
        $path = $repository->getPath();
        if ( empty($path) ) {
            throw new GitBackendException('Bad repository path: '.$path);
        }
        $path = $this->getGitRootPath().DIRECTORY_SEPARATOR.$path;        
        if ($ignoreHasChildren === false && $this->getDao()->hasChild($repository) === true) {
            throw new GitBackendException( $GLOBALS['Language']->getText('plugin_git', 'backend_delete_haschild_error') );
        }
        
        if ($repository->canBeDeleted()) {
            $this->archive($repository);
            $this->getDao()->delete($repository);        
            $this->getDriver()->delete($path);
            return true;
        } else {
            throw new GitBackendException( $GLOBALS['Language']->getText('plugin_git', 'backend_delete_path_error') );
        }
        
    }

    /**
     * Delete all repositories of a project
     *
     * @param Integer $projectId Id of the project
     *
     * @return Boolean
     */
    public function deleteProjectRepositories($projectId) {
        $deleteStatus   = true;
        $repositoryList = $this->getDao()->getProjectRepositoryList($projectId, true);
        if (!empty($repositoryList)) {
            $sem = $this->getSystemEventManager();
            foreach ($repositoryList as $repositoryId => $repoData) {
                $sem->createEvent('GIT_REPO_DELETE',
                                   $projectId.SystemEvent::PARAMETER_SEPARATOR.$repositoryId.SystemEvent::PARAMETER_SEPARATOR.true,
                                   SystemEvent::PRIORITY_MEDIUM);
            }
        }
        return $deleteStatus;
    }

    function getSystemEventManager() {
        return SystemEventManager::instance();
    }

    public function save($repository) {
        $path          = Git_Backend_Interface::GIT_ROOT_PATH .'/'.$repository->getPath();
        $fsDescription = $this->getDriver()->getDescription($path);
        $description   = $repository->getDescription();
        if ( $description != $fsDescription ) {
            $this->getDriver()->setDescription( $path, $description );
        }
        $this->getDao()->save($repository);
    }

    public function renameProject(Project $project, $newName) {
        if (is_dir(Git_Backend_Interface::GIT_ROOT_PATH .'/'.$project->getUnixName())) {
            return rename(Git_Backend_Interface::GIT_ROOT_PATH .'/'.$project->getUnixName(), Git_Backend_Interface::GIT_ROOT_PATH .'/'.$newName);
        }
        return true;
    }

    public function isInitialized($repository) {
        $masterExists = $this->getDriver()->masterExists( $this->getGitRootPath().'/'.$repository->getPath() );
        if ( $masterExists ) {
            $this->getDao()->initialize( $repository->getId() );
            return true;
        } else {
            return false;
        }
    }

    public function changeRepositoryAccess($repository) {
        $access   = $repository->getAccess();
        $repoPath = $repository->getPath();
        $path     = Git_Backend_Interface::GIT_ROOT_PATH .'/'.$repoPath;        
        $this->getDriver()->setRepositoryAccess($path, $access);
        $this->getDao()->save($repository);
        return true;
    }

    /**
     * Allow the update of the mail prefix
     *
     * @param GitRepository $repository
     */
    public function changeRepositoryMailPrefix($repository) {
        if ($this->getDao()->save($repository)) {
            $path = $this->getGitRootPath().$repository->getPath();
            $this->getDriver()->setConfig($path, 'hooks.emailprefix', $repository->getMailPrefix());
            $this->setUpMailingHook($repository);
            return true;
        }
        return false;
    }

    /**
     * Update list of people notified by post-receive-email hook
     *
     * @param GitRepository $repository
     */
    public function changeRepositoryMailingList($repository) {
        $path = $this->getGitRootPath().$repository->getPath();
        $this->getDriver()->setConfig($path, 'hooks.mailinglist', implode(',', $repository->getNotifiedMails()));
        $this->setUpMailingHook($repository);
        return true;
    }

    /**
     * Deploy post-receive hook into the target file
     *
     * @param String $path Path to the repository root
     *
     * @return void
     */
    public function deployPostReceive($path) {
        $this->getDriver()->activateHook('post-receive', $path);
        $hook = '. '.$GLOBALS['sys_pluginsroot'].'git/hooks/post-receive 2>/dev/null';
        $this->addBlock($path.'/hooks/post-receive', $hook);
    }

    /**
     * Configure mail output to link commit to gitweb 
     *
     * @param GitRepository $repository
     */
    public function setUpMailingHook($repository) {
        $path = $this->getGitRootPath().$repository->getPath();
        $this->getDriver()->setConfig($path, 'hooks.showrev', $repository->getPostReceiveShowRev());
    }


    /**
     * INTERNAL METHODS
     */

    protected function setRepositoryPermissions($repository) {
        $path = $this->getGitRootPath().DIRECTORY_SEPARATOR.$repository->getPath();   
        $this->recurseChownChgrp($path, 'codendiadm',$repository->getProject()->getUnixName() );
        return true;
    }

    protected function createGitRoot() {
        $gitRootPath    = $this->getGitRootPath();        
        //create the gitroot directory
        if ( !is_dir($gitRootPath) ) {
            if ( !mkdir($gitRootPath, 0755) ) {
                throw new GitBackendException( $GLOBALS['Language']->getText('plugin_git', 'backend_gitroot_mkdir_error').' -> '.$gitRootPath );
            }            
        }
        return true;
    }

    //TODO : public project
    protected function createProjectRoot($repository) {
        $gitProjectPath = $this->getGitRootPath().DIRECTORY_SEPARATOR.$repository->getRootPath();
        $groupName      = $repository->getProject()->getUnixName();
        if ( !is_dir($gitProjectPath) ) {

            if ( !mkdir($gitProjectPath, 0775, true) ) {
                throw new GitBackendException($GLOBALS['Language']->getText('plugin_git', 'backend_projectroot_mkdir_error').' -> '.$gitProjectPath);
            }

            if ( !$this->chgrp($gitProjectPath, $groupName ) ) {
                throw new GitBackendException($GLOBALS['Language']->getText('plugin_git', 'backend_projectroot_chgrp_error').$gitProjectPath.' group='.$groupName);
            }            
        }
        return true;
    }

    /**
     *@todo move the archive to another directory
     * @param <type> $repository
     * @return <type>
     */
    protected function archive($repository) {
        chdir( $this->getGitRootPath() );
        $path = $repository->getPath();
        $name = $repository->getName();
        $date = $repository->getDeletionDate();
        $projectName = $repository->getProject()->getUnixName();
        $archiveName = $projectName.'_'.$name.'_'.strtotime($date).'.tar.bz2 ';
        $cmd    = ' tar cjf '.$archiveName.' '.$path;
        $rcode  = 0 ;
        $output = $this->system( $cmd, $rcode );        
        if ( $rcode != 0 ) {
            throw new GitBackendException($cmd.' -> '.$output);
        }
        if ( !empty($this->gitBackupDir) && is_dir($this->gitBackupDir) ) {
            $this->system( 'mv '.$this->getGitRootPath().'/'.$archiveName.' '.$this->gitBackupDir.'/'.$archiveName );
        }
        return true;
    }    

    /**
     * Verify if given name is not already reserved on filesystem
     */
    public function isNameAvailable($newName) {
        return ! $this->fileExists(Git_Backend_Interface::GIT_ROOT_PATH .'/'.$newName);
    }

    /**
     * Return URL to access the respository for remote git commands
     *
     * @param  GitRepository $repository
     * @return String
     */
    public function getAccessUrl(GitRepository $repository) {
        $serverName  = $_SERVER['SERVER_NAME'];
        $user = UserManager::instance()->getCurrentUser();
        return  $user->getUserName() .'@'. $serverName .':/gitroot/'. $repository->getProject()->getUnixName().'/'.$repository->getName().'.git';
    }

    /**
     * Test is user can read the content of this repository and metadata
     *
     * @param User          $user       The user to test
     * @param GitRepository $repository The repository to test
     *
     * @return Boolean
     */
    public function userCanRead($user, $repository) {
        if ($repository->isPrivate() && $user->isMember($repository->getProjectId())) {
            return true;
        }
        if ($repository->isPublic()) {
            if ($user->isRestricted() && $user->isMember($repository->getProjectId())) {
                return true;
            }
            if (!$user->isAnonymous()) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get the regexp pattern to use for name repository validation
     *
     * @return string
     */
    public function getAllowedCharsInNamePattern() {
        //alphanums, underscores and dash
        return 'a-zA-Z0-9_.-';
    }

    /**
     * Obtain statistics about backend format for CSV export
     *
     * @param Statistics_Scm $scmStats instance of statistics class
     *
     * @return String
     */
    public function getBackendStatistics($scmStats) {
        $dates = $scmStats->splitPeriodByMonths();
        $dao = $this->getDao();
        $scmStats->clearContent();
        $scmStats->addLine(array());
        $scmStats->addLine(array('Git'));
        $periods[]  = $GLOBALS['Language']->getText('plugin_statistics', 'scm_period');
        $gitShell[] = "Git shell";
        $gitolite[] = "Gitolite";
        foreach ($dates as $begin => $end) {
            if ($begin) {
                $periods[] = $begin." -> ".$end;
                $gitShell[$begin.$end] = 0;
                $gitolite[$begin.$end] = 0;
                $dar                      = $dao->getBackendStatistics($begin, $end, $scmStats->groupId);
                if ($dar && !$dar->isError()) {
                    foreach ($dar as $row) {
                        if ($row['backend'] == "gitshell") {
                            $gitShell[$begin.$end] = intval($row['count']);
                        } elseif ($row['backend'] == "gitolite") {
                            $gitolite[$begin.$end] = intval($row['count']);
                        }
                    }
                }
            }
        }
        $scmStats->addLine($periods);
        $scmStats->addLine($gitShell);
        $scmStats->addLine($gitolite);
        $content = $scmStats->getStats();
        $scmStats->clearContent();
        return $content;
    }
}

?>

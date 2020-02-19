<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Docman\XML\Import;

use Docman_FileStorage;
use Docman_Item;
use Docman_VersionFactory;
use PFUser;
use Project;
use SimpleXMLElement;

class VersionImporter
{
    /**
     * @var Docman_VersionFactory
     */
    private $version_factory;
    /**
     * @var Docman_FileStorage
     */
    private $docman_file_storage;
    /**
     * @var string
     */
    private $extraction_path;
    /**
     * @var Project
     */
    private $project;

    public function __construct(
        Docman_VersionFactory $version_factory,
        Docman_FileStorage $docman_file_storage,
        Project $project,
        string $extraction_path
    ) {
        $this->version_factory     = $version_factory;
        $this->docman_file_storage = $docman_file_storage;
        $this->project             = $project;
        $this->extraction_path     = $extraction_path;
    }

    /**
     * @throws UnableToCreateFileOnFilesystemException|UnableToCreateVersionInDbException
     */
    public function import(SimpleXMLElement $node, Docman_Item $item, \PFUser $user, int $version_number)
    {
        $file_path = $this->createFileOnFilesystem($node, $item, $version_number);
        $this->createVersionEntryInDb($item, $version_number, $user, $node, $file_path);
    }

    /**
     * @throws UnableToCreateFileOnFilesystemException
     */
    private function createFileOnFilesystem(SimpleXMLElement $node, Docman_Item $item, int $version_number): string
    {
        $file_path = $this->docman_file_storage->copy(
            $this->extraction_path . '/' . $node->content,
            (string) $node->filename,
            $this->project->getGroupId(),
            $item->getId(),
            $version_number
        );
        if ($file_path === false) {
            throw new UnableToCreateFileOnFilesystemException($version_number, $item);
        }

        return $file_path;
    }

    /**
     * @throws UnableToCreateVersionInDbException
     */
    private function createVersionEntryInDb(
        Docman_Item $item,
        int $version_number,
        PFUser $user,
        SimpleXMLElement $version,
        string $file_path
    ): void {
        $is_item_created = $this->version_factory->create(
            [
                'item_id'  => $item->getId(),
                'number'   => $version_number,
                'user_id'  => $user->getId(),
                'filename' => (string) $version->filename,
                'filesize' => (int) $version->filesize,
                'filetype' => (string) $version->filetype,
                'path'     => $file_path,
                'date'     => (new \DateTimeImmutable)->getTimestamp(),
            ]
        );
        if ($is_item_created === false) {
            \unlink($file_path);
            throw new UnableToCreateVersionInDbException($version_number, $item);
        }
    }
}
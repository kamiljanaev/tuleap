<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Tracker;

use PFUser;
use Planning_Milestone;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedFieldRetriever;

class TrackerPresenterCollectionBuilder
{
    /** @var TrackerCollectionRetriever */
    private $trackers_retriever;
    /** @var MappedFieldRetriever */
    private $mapped_field_retriever;

    public function __construct(
        TrackerCollectionRetriever $trackers_retriever,
        MappedFieldRetriever $mapped_field_retriever
    ) {
        $this->trackers_retriever     = $trackers_retriever;
        $this->mapped_field_retriever = $mapped_field_retriever;
    }

    public static function build(): self
    {
        return new self(TrackerCollectionRetriever::build(), MappedFieldRetriever::build());
    }

    /**
     * @return TrackerPresenter[]
     */
    public function buildCollection(Planning_Milestone $milestone, PFUser $user): array
    {
        return $this->trackers_retriever->getTrackersForMilestone($milestone)->map(
            function (TaskboardTracker $taskboard_tracker) use ($user) {
                $mapped_field = $this->mapped_field_retriever->getField($taskboard_tracker);
                $title_field_id = $this->getTitleFieldId($taskboard_tracker, $user);

                if (! $mapped_field) {
                    return new TrackerPresenter($taskboard_tracker, false, $title_field_id);
                }
                return new TrackerPresenter($taskboard_tracker, $mapped_field->userCanUpdate($user), $title_field_id);
            }
        );
    }

    private function getTitleFieldId(TaskboardTracker $taskboard_tracker, \PFUser $user): ?int
    {
        $field_title = \Tracker_Semantic_Title::load($taskboard_tracker->getTracker())->getField();

        return ($field_title !== null && $field_title->userCanUpdate($user))
            ? (int) $field_title->getId()
            : null;
    }
}
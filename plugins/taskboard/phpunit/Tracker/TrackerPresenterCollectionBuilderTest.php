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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning_Milestone;
use Tracker;
use Tracker_FormElement_Field_Selectbox;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedFieldRetriever;

final class TrackerPresenterCollectionBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var TrackerPresenterCollectionBuilder */
    private $trackers_builder;
    /** @var M\LegacyMockInterface|M\MockInterface|TrackerCollectionRetriever */
    private $trackers_retriever;
    /** @var M\LegacyMockInterface|M\MockInterface|MappedFieldRetriever */
    private $mapped_field_retriever;
    /** @var \Tracker_Semantic_Title */
    private $semantic_title;

    protected function setUp(): void
    {
        $this->trackers_retriever     = M::mock(TrackerCollectionRetriever::class);
        $this->mapped_field_retriever = M::mock(MappedFieldRetriever::class);
        $this->semantic_title         = M::mock(\Tracker_Semantic_Title::class);
        $this->trackers_builder       = new TrackerPresenterCollectionBuilder(
            $this->trackers_retriever,
            $this->mapped_field_retriever
        );
    }

    protected function tearDown() : void
    {
        \Tracker_Semantic_Title::clearInstances();
    }

    public function testBuildCollectionReturnsEmptyArrayWhenNoTrackers(): void
    {
        $milestone = M::mock(Planning_Milestone::class);
        $user      = M::mock(PFUser::class);
        $this->trackers_retriever->shouldReceive('getTrackersForMilestone')
            ->with($milestone)
            ->once()
            ->andReturn(new TrackerCollection([]));
        $this->mapped_field_retriever->shouldNotReceive('getField');

        $result = $this->trackers_builder->buildCollection($milestone, $user);
        $this->assertSame(0, count($result));
    }

    public function testBuildCollectionReturnsCannotUpdateWhenNoMappedFieldAndCannotUpdateTitle(): void
    {
        $milestone         = M::mock(Planning_Milestone::class);
        $user              = M::mock(PFUser::class);
        $milestone_tracker = M::mock(Tracker::class);
        $tracker           = $this->mockTracker('27');
        $taskboard_tracker = new TaskboardTracker($milestone_tracker, $tracker);
        $this->trackers_retriever->shouldReceive('getTrackersForMilestone')
            ->with($milestone)
            ->once()
            ->andReturn(new TrackerCollection([$taskboard_tracker]));
        $this->mapped_field_retriever->shouldReceive('getField')
            ->with($taskboard_tracker)
            ->once()
            ->andReturnNull();

        $this->mockSemanticTitle($taskboard_tracker, true, false);

        $result = $this->trackers_builder->buildCollection($milestone, $user);
        $this->assertFalse($result[0]->can_update_mapped_field);
        $this->assertNull($result[0]->title_field_id);
    }

    public function testBuildCollectionReturnsTrackerPresenters(): void
    {
        $milestone                = M::mock(Planning_Milestone::class);
        $user                     = M::mock(PFUser::class);
        $milestone_tracker        = M::mock(Tracker::class);
        $first_tracker            = $this->mockTracker('27');
        $second_tracker           = $this->mockTracker('85');
        $first_taskboard_tracker  = new TaskboardTracker($milestone_tracker, $first_tracker);
        $second_taskboard_tracker = new TaskboardTracker($milestone_tracker, $second_tracker);
        $this->trackers_retriever->shouldReceive('getTrackersForMilestone')
            ->with($milestone)
            ->once()
            ->andReturn(new TrackerCollection([$first_taskboard_tracker, $second_taskboard_tracker]));
        $sb_field_can_update = M::mock(Tracker_FormElement_Field_Selectbox::class);
        $sb_field_can_update->shouldReceive('userCanUpdate')
            ->with($user)
            ->once()
            ->andReturnTrue();
        $sb_field_cannot_update = M::mock(Tracker_FormElement_Field_Selectbox::class);
        $sb_field_cannot_update->shouldReceive('userCanUpdate')
            ->with($user)
            ->once()
            ->andReturnFalse();
        $this->mapped_field_retriever->shouldReceive('getField')
            ->with($first_taskboard_tracker)
            ->once()
            ->andReturn($sb_field_can_update);
        $this->mapped_field_retriever->shouldReceive('getField')
            ->with($second_taskboard_tracker)
            ->once()
            ->andReturn($sb_field_cannot_update);

        $this->mockSemanticTitle($first_taskboard_tracker, false, true);
        $this->mockSemanticTitle($second_taskboard_tracker, true, true);

        $result = $this->trackers_builder->buildCollection($milestone, $user);
        $this->assertSame(27, $result[0]->id);
        $this->assertTrue($result[0]->can_update_mapped_field);
        $this->assertNull($result[0]->title_field_id);
        $this->assertSame(85, $result[1]->id);
        $this->assertFalse($result[1]->can_update_mapped_field);
        $this->assertEquals(1533, $result[1]->title_field_id);
    }

    /**
     * @return M\LegacyMockInterface|M\MockInterface|Tracker
     */
    private function mockTracker(string $id)
    {
        return M::mock(Tracker::class)->shouldReceive('getId')
            ->andReturn($id)
            ->getMock();
    }

    private function mockSemanticTitle(TaskboardTracker $taskboard_tracker, bool $is_semantic_set, bool $can_user_update) : void
    {
        \Tracker_Semantic_Title::setInstance($this->semantic_title, $taskboard_tracker->getTracker());

        $title_field = null;

        if ($is_semantic_set) {
            $title_field = M::mock(\Tracker_FormElement_Field_Text::class);
            $title_field->shouldReceive('getId')->andReturn(1533);
            $title_field->shouldReceive('userCanUpdate')->andReturn($can_user_update);
        }

        $this->semantic_title->shouldReceive('getField')->andReturn($title_field)->once();
    }
}
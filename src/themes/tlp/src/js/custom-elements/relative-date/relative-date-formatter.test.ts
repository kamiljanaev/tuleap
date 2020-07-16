/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import formatRelativeDate from "./relative-date-formatter";

const a_minute_in_ms = 60 * 1000;
const a_hour_in_ms = 60 * a_minute_in_ms;
const a_day_in_ms = 24 * a_hour_in_ms;
const a_month_in_ms = 30 * a_day_in_ms;
const a_year_in_ms = 12 * a_month_in_ms;

describe("relative-date-formatter", () => {
    it("Displays X minutes ago", () => {
        expect(formatRelativeDate("en-US", new Date())).toBe("0 minutes ago");
        expect(formatRelativeDate("en-US", new Date(Date.now() - a_minute_in_ms))).toBe(
            "1 minute ago"
        );
        expect(formatRelativeDate("en-US", new Date(Date.now() - 44 * a_minute_in_ms))).toBe(
            "44 minutes ago"
        );
    });
    it("Displays X hours ago", () => {
        expect(formatRelativeDate("en-US", new Date(Date.now() - 45 * a_minute_in_ms))).toBe(
            "1 hour ago"
        );
        expect(formatRelativeDate("en-US", new Date(Date.now() - a_hour_in_ms))).toBe("1 hour ago");
        expect(formatRelativeDate("en-US", new Date(Date.now() - 2 * a_hour_in_ms))).toBe(
            "2 hours ago"
        );
        expect(formatRelativeDate("en-US", new Date(Date.now() - 23 * a_hour_in_ms))).toBe(
            "23 hours ago"
        );
    });
    it("Displays X days ago", () => {
        expect(formatRelativeDate("en-US", new Date(Date.now() - a_day_in_ms))).toBe("1 day ago");
        expect(formatRelativeDate("en-US", new Date(Date.now() - 2 * a_day_in_ms))).toBe(
            "2 days ago"
        );
        expect(formatRelativeDate("en-US", new Date(Date.now() - 29 * a_day_in_ms))).toBe(
            "29 days ago"
        );
    });
    it("Displays X months ago", () => {
        expect(formatRelativeDate("en-US", new Date(Date.now() - a_month_in_ms))).toBe(
            "1 month ago"
        );
        expect(formatRelativeDate("en-US", new Date(Date.now() - 2 * a_month_in_ms))).toBe(
            "2 months ago"
        );
        expect(formatRelativeDate("en-US", new Date(Date.now() - 11 * a_month_in_ms))).toBe(
            "11 months ago"
        );
    });
    it("Displays X years ago", () => {
        expect(formatRelativeDate("en-US", new Date(Date.now() - a_year_in_ms))).toBe("1 year ago");
        expect(formatRelativeDate("en-US", new Date(Date.now() - 2 * a_year_in_ms))).toBe(
            "2 years ago"
        );
    });
});
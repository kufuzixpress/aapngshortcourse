-- ---------------------------------------------------------------------------
-- Adds a planned end date to awards (scholarships), kept separate from the
-- actual end date so late or early completions can be reported on.
--
--   planned_end_date  entered when the award is recorded (Add / Edit)
--   study_end_date    the actual end date, confirmed when the award is finalised
--
-- Run once, after taking a backup:
--   mysql -u <user> -p <database> < database/migrations/2026_10_28_add_planned_end_date.sql
-- ---------------------------------------------------------------------------

ALTER TABLE scholarships
    ADD COLUMN planned_end_date DATE NULL AFTER study_start_date;

-- Until now study_end_date held the planned date for every award, so copy it
-- across. For completed awards this is the best planned date we have.
UPDATE scholarships
SET planned_end_date = study_end_date
WHERE planned_end_date IS NULL;

-- Awards that are not completed yet have no actual end date — that is now
-- recorded at finalisation. The value was copied above, so nothing is lost.
UPDATE scholarships s
LEFT JOIN scholarship_statuses st ON st.id = s.status_id
SET s.study_end_date = NULL
WHERE LOWER(COALESCE(st.name, '')) <> 'completed';

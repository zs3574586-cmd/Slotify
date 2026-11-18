CREATE TABLE IF NOT EXISTS slots (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    starts_at  TEXT NOT NULL UNIQUE,   -- 'Y-m-d H:i'
    ends_at    TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS bookings (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    slot_id    INTEGER NOT NULL REFERENCES slots(id) ON DELETE RESTRICT,
    name       TEXT NOT NULL,
    email      TEXT NOT NULL,
    note       TEXT NOT NULL DEFAULT '',
    token      TEXT NOT NULL UNIQUE,
    status     TEXT NOT NULL DEFAULT 'confirmed' CHECK (status IN ('confirmed','cancelled')),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
-- The double-booking guard: only ONE confirmed booking may exist per slot.
-- Cancelled rows are exempt, so a cancelled slot becomes bookable again.
CREATE UNIQUE INDEX IF NOT EXISTS one_active_booking_per_slot
    ON bookings(slot_id) WHERE status = 'confirmed';

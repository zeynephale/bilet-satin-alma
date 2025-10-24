-- Migration: Add bus_type column to trips table
-- Run this if you have existing data you want to keep

-- Add bus_type column with default value
ALTER TABLE trips ADD COLUMN bus_type TEXT NOT NULL DEFAULT '2+2' CHECK(bus_type IN ('2+1', '2+2', '3+2'));

-- Update existing trips to have a valid bus_type based on seat count
UPDATE trips SET bus_type = CASE
    WHEN seats <= 36 THEN '2+1'
    WHEN seats <= 44 THEN '2+2'
    ELSE '3+2'
END
WHERE bus_type = '2+2';





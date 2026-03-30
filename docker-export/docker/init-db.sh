#!/bin/bash
# Create the pool_schema if it doesn't exist
psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -c "CREATE SCHEMA IF NOT EXISTS pool_schema;"

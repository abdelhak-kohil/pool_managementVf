--
-- PostgreSQL database dump
--

\restrict jPeTn0GIwdCEcRcHWFN0SQ1JVl45CrbYlahJIjKkaJo3HKZIzRbgvl7wDHl1B8c

-- Dumped from database version 18.0
-- Dumped by pg_dump version 18.0

-- Started on 2025-12-07 08:44:20

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 6 (class 2615 OID 25846)
-- Name: pool_schema; Type: SCHEMA; Schema: -; Owner: pooladmin
--

CREATE SCHEMA "pool_schema";


ALTER SCHEMA "pool_schema" OWNER TO "pooladmin";

--
-- TOC entry 947 (class 1247 OID 25849)
-- Name: access_decision_enum; Type: TYPE; Schema: pool_schema; Owner: pooladmin
--

CREATE TYPE "pool_schema"."access_decision_enum" AS ENUM (
    'granted',
    'denied'
);


ALTER TYPE "pool_schema"."access_decision_enum" OWNER TO "pooladmin";

--
-- TOC entry 950 (class 1247 OID 25854)
-- Name: badge_status_enum; Type: TYPE; Schema: pool_schema; Owner: pooladmin
--

CREATE TYPE "pool_schema"."badge_status_enum" AS ENUM (
    'active',
    'inactive',
    'lost',
    'revoked',
    'blocked'
);


ALTER TYPE "pool_schema"."badge_status_enum" OWNER TO "pooladmin";

--
-- TOC entry 953 (class 1247 OID 25866)
-- Name: facility_status_enum; Type: TYPE; Schema: pool_schema; Owner: pooladmin
--

CREATE TYPE "pool_schema"."facility_status_enum" AS ENUM (
    'operational',
    'under_maintenance',
    'closed'
);


ALTER TYPE "pool_schema"."facility_status_enum" OWNER TO "pooladmin";

--
-- TOC entry 956 (class 1247 OID 25874)
-- Name: payment_method_enum; Type: TYPE; Schema: pool_schema; Owner: pooladmin
--

CREATE TYPE "pool_schema"."payment_method_enum" AS ENUM (
    'cash',
    'card',
    'Virement',
    'transfer'
);


ALTER TYPE "pool_schema"."payment_method_enum" OWNER TO "pooladmin";

--
-- TOC entry 959 (class 1247 OID 25884)
-- Name: plan_type_enum; Type: TYPE; Schema: pool_schema; Owner: pooladmin
--

CREATE TYPE "pool_schema"."plan_type_enum" AS ENUM (
    'monthly_weekly',
    'per_visit'
);


ALTER TYPE "pool_schema"."plan_type_enum" OWNER TO "pooladmin";

--
-- TOC entry 962 (class 1247 OID 25890)
-- Name: subscription_status_enum; Type: TYPE; Schema: pool_schema; Owner: pooladmin
--

CREATE TYPE "pool_schema"."subscription_status_enum" AS ENUM (
    'active',
    'paused',
    'expired',
    'cancelled'
);


ALTER TYPE "pool_schema"."subscription_status_enum" OWNER TO "pooladmin";

--
-- TOC entry 314 (class 1255 OID 25899)
-- Name: fn_audit_log_changes(); Type: FUNCTION; Schema: pool_schema; Owner: pooladmin
--

CREATE FUNCTION "pool_schema"."fn_audit_log_changes"() RETURNS "trigger"
    LANGUAGE "plpgsql" SECURITY DEFINER
    AS $$
DECLARE
    v_action TEXT;
    v_old_data JSONB;
    v_new_data JSONB;
    v_record_id TEXT;
BEGIN
RAISE NOTICE 'Trigger executed at % with data: %', now(), NEW;

    IF TG_OP = 'INSERT' THEN
        v_action := 'INSERT';
        v_old_data := NULL;
        v_new_data := to_jsonb(NEW);
        v_record_id := COALESCE(
            (to_jsonb(NEW)->>'member_id'),
            (to_jsonb(NEW)->>'subscription_id'),
            (to_jsonb(NEW)->>'badge_id'),
            (to_jsonb(NEW)->>'staff_id'),
			(to_jsonb(NEW)->>'activity_id'),
            (to_jsonb(NEW)->>'id')
        );

    ELSIF TG_OP = 'UPDATE' THEN
        v_action := 'UPDATE';
        v_old_data := to_jsonb(OLD);
        v_new_data := to_jsonb(NEW);

        -- Ignore updates with no actual change
        v_old_data := v_old_data - 'updated_at';
        v_new_data := v_new_data - 'updated_at';
        IF v_old_data = v_new_data THEN
            RETURN NEW;
        END IF;

        v_record_id := COALESCE(
            (to_jsonb(NEW)->>'member_id'),
            (to_jsonb(NEW)->>'subscription_id'),
            (to_jsonb(NEW)->>'badge_id'),
            (to_jsonb(NEW)->>'staff_id'),
			(to_jsonb(NEW)->>'activity_id'),
            (to_jsonb(NEW)->>'id')
        );

    ELSIF TG_OP = 'DELETE' THEN
        v_action := 'DELETE';
        v_old_data := to_jsonb(OLD);
        v_new_data := NULL;
        v_record_id := COALESCE(
            (to_jsonb(OLD)->>'member_id'),
            (to_jsonb(OLD)->>'subscription_id'),
            (to_jsonb(OLD)->>'badge_id'),
            (to_jsonb(OLD)->>'staff_id'),
			(to_jsonb(OLD)->>'activity_id'),
            (to_jsonb(OLD)->>'id')
        );
    END IF;

    INSERT INTO pool_schema.audit_log (
        table_name,
        record_id,
        action,
        changed_by_staff_id,
        change_timestamp,
        old_data_jsonb,
        new_data_jsonb
    )
    VALUES (
        TG_TABLE_SCHEMA || '.' || TG_TABLE_NAME,
        v_record_id,
        v_action,
        current_setting('app.current_staff_id', true)::BIGINT,
        NOW(),
        v_old_data,
        v_new_data
    );

    RETURN NEW;
	
END;
$$;


ALTER FUNCTION "pool_schema"."fn_audit_log_changes"() OWNER TO "pooladmin";

--
-- TOC entry 313 (class 1255 OID 25900)
-- Name: fn_cleanup_old_audit_logs(integer); Type: FUNCTION; Schema: pool_schema; Owner: postgres
--

CREATE FUNCTION "pool_schema"."fn_cleanup_old_audit_logs"("retention_days" integer DEFAULT 2) RETURNS "void"
    LANGUAGE "plpgsql" SECURITY DEFINER
    AS $$
DECLARE
    v_cutoff TIMESTAMPTZ := NOW() - (retention_days || ' days')::INTERVAL;
    v_deleted_count INTEGER;
BEGIN
    DELETE FROM pool_schema.audit_log
    WHERE change_timestamp < v_cutoff
    RETURNING 1 INTO v_deleted_count;

    RAISE NOTICE '🧹 % old audit logs deleted (older than % days).', 
        COALESCE(v_deleted_count, 0), retention_days;
END;
$$;


ALTER FUNCTION "pool_schema"."fn_cleanup_old_audit_logs"("retention_days" integer) OWNER TO "postgres";

SET default_tablespace = '';

SET default_table_access_method = "heap";

--
-- TOC entry 262 (class 1259 OID 42898)
-- Name: access_badges; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."access_badges" (
    "badge_id" bigint NOT NULL,
    "member_id" bigint,
    "badge_uid" character varying(255) NOT NULL,
    "status" character varying(255) DEFAULT 'active'::character varying NOT NULL,
    "issued_at" timestamp(0) without time zone,
    "expires_at" timestamp(0) without time zone,
    "staff_id" bigint
);


ALTER TABLE "pool_schema"."access_badges" OWNER TO "pooladmin";

--
-- TOC entry 261 (class 1259 OID 42897)
-- Name: access_badges_badge_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."access_badges_badge_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."access_badges_badge_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5518 (class 0 OID 0)
-- Dependencies: 261
-- Name: access_badges_badge_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."access_badges_badge_id_seq" OWNED BY "pool_schema"."access_badges"."badge_id";


--
-- TOC entry 264 (class 1259 OID 42918)
-- Name: access_logs; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."access_logs" (
    "log_id" bigint NOT NULL,
    "badge_uid" character varying(255),
    "member_id" bigint,
    "access_time" timestamp(0) without time zone NOT NULL,
    "access_decision" character varying(255) NOT NULL,
    "denial_reason" character varying(255),
    "activity_id" bigint,
    "subscription_id" bigint,
    "slot_id" bigint,
    "staff_id" bigint
);


ALTER TABLE "pool_schema"."access_logs" OWNER TO "pooladmin";

--
-- TOC entry 263 (class 1259 OID 42917)
-- Name: access_logs_log_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."access_logs_log_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."access_logs_log_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5519 (class 0 OID 0)
-- Dependencies: 263
-- Name: access_logs_log_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."access_logs_log_id_seq" OWNED BY "pool_schema"."access_logs"."log_id";


--
-- TOC entry 250 (class 1259 OID 42752)
-- Name: activities; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."activities" (
    "activity_id" bigint NOT NULL,
    "name" character varying(255) NOT NULL,
    "description" "text",
    "access_type" character varying(255),
    "color_code" character varying(255),
    "is_active" boolean DEFAULT true NOT NULL
);


ALTER TABLE "pool_schema"."activities" OWNER TO "pooladmin";

--
-- TOC entry 249 (class 1259 OID 42751)
-- Name: activities_activity_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."activities_activity_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."activities_activity_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5520 (class 0 OID 0)
-- Dependencies: 249
-- Name: activities_activity_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."activities_activity_id_seq" OWNED BY "pool_schema"."activities"."activity_id";


--
-- TOC entry 268 (class 1259 OID 42955)
-- Name: activity_plan_prices; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."activity_plan_prices" (
    "id" bigint NOT NULL,
    "activity_id" bigint NOT NULL,
    "plan_id" bigint NOT NULL,
    "price" numeric(8,2) NOT NULL
);


ALTER TABLE "pool_schema"."activity_plan_prices" OWNER TO "pooladmin";

--
-- TOC entry 267 (class 1259 OID 42954)
-- Name: activity_plan_prices_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."activity_plan_prices_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."activity_plan_prices_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5521 (class 0 OID 0)
-- Dependencies: 267
-- Name: activity_plan_prices_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."activity_plan_prices_id_seq" OWNED BY "pool_schema"."activity_plan_prices"."id";


--
-- TOC entry 312 (class 1259 OID 43516)
-- Name: backup_jobs; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."backup_jobs" (
    "id" bigint NOT NULL,
    "backup_type" character varying(255) NOT NULL,
    "file_name" character varying(255) NOT NULL,
    "file_size" character varying(255),
    "storage_location" character varying(255) NOT NULL,
    "status" character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    "started_at" timestamp(0) without time zone,
    "completed_at" timestamp(0) without time zone,
    "error_message" "text",
    "triggered_by" bigint,
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone
);


ALTER TABLE "pool_schema"."backup_jobs" OWNER TO "pooladmin";

--
-- TOC entry 311 (class 1259 OID 43515)
-- Name: backup_jobs_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."backup_jobs_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."backup_jobs_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5522 (class 0 OID 0)
-- Dependencies: 311
-- Name: backup_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."backup_jobs_id_seq" OWNED BY "pool_schema"."backup_jobs"."id";


--
-- TOC entry 310 (class 1259 OID 43496)
-- Name: backup_settings; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."backup_settings" (
    "id" bigint NOT NULL,
    "automatic_enabled" boolean DEFAULT false NOT NULL,
    "scheduled_time" time(0) without time zone DEFAULT '00:00:00'::time without time zone NOT NULL,
    "frequency" character varying(255) DEFAULT 'daily'::character varying NOT NULL,
    "retention_days" integer DEFAULT 30 NOT NULL,
    "storage_preference" character varying(255) DEFAULT 'local'::character varying NOT NULL,
    "network_path" character varying(255),
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone
);


ALTER TABLE "pool_schema"."backup_settings" OWNER TO "pooladmin";

--
-- TOC entry 309 (class 1259 OID 43495)
-- Name: backup_settings_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."backup_settings_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."backup_settings_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5523 (class 0 OID 0)
-- Dependencies: 309
-- Name: backup_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."backup_settings_id_seq" OWNED BY "pool_schema"."backup_settings"."id";


--
-- TOC entry 232 (class 1259 OID 42606)
-- Name: cache; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."cache" (
    "key" character varying(255) NOT NULL,
    "value" "text" NOT NULL,
    "expiration" integer NOT NULL
);


ALTER TABLE "pool_schema"."cache" OWNER TO "pooladmin";

--
-- TOC entry 233 (class 1259 OID 42616)
-- Name: cache_locks; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."cache_locks" (
    "key" character varying(255) NOT NULL,
    "owner" character varying(255) NOT NULL,
    "expiration" integer NOT NULL
);


ALTER TABLE "pool_schema"."cache_locks" OWNER TO "pooladmin";

--
-- TOC entry 270 (class 1259 OID 42976)
-- Name: categories; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."categories" (
    "id" bigint NOT NULL,
    "name" character varying(255) NOT NULL,
    "type" character varying(255),
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone
);


ALTER TABLE "pool_schema"."categories" OWNER TO "pooladmin";

--
-- TOC entry 269 (class 1259 OID 42975)
-- Name: categories_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."categories_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."categories_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5524 (class 0 OID 0)
-- Dependencies: 269
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."categories_id_seq" OWNED BY "pool_schema"."categories"."id";


--
-- TOC entry 284 (class 1259 OID 43123)
-- Name: coach_time_slot; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."coach_time_slot" (
    "id" bigint NOT NULL,
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone
);


ALTER TABLE "pool_schema"."coach_time_slot" OWNER TO "pooladmin";

--
-- TOC entry 283 (class 1259 OID 43122)
-- Name: coach_time_slot_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."coach_time_slot_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."coach_time_slot_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5525 (class 0 OID 0)
-- Dependencies: 283
-- Name: coach_time_slot_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."coach_time_slot_id_seq" OWNED BY "pool_schema"."coach_time_slot"."id";


--
-- TOC entry 282 (class 1259 OID 43097)
-- Name: expenses; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."expenses" (
    "expense_id" bigint NOT NULL,
    "title" character varying(255) NOT NULL,
    "amount" numeric(10,2) NOT NULL,
    "expense_date" "date" NOT NULL,
    "category" character varying(255) NOT NULL,
    "description" "text",
    "payment_method" character varying(255) DEFAULT 'cash'::character varying NOT NULL,
    "reference" character varying(255),
    "created_by" bigint,
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone
);


ALTER TABLE "pool_schema"."expenses" OWNER TO "pooladmin";

--
-- TOC entry 281 (class 1259 OID 43096)
-- Name: expenses_expense_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."expenses_expense_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."expenses_expense_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5526 (class 0 OID 0)
-- Dependencies: 281
-- Name: expenses_expense_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."expenses_expense_id_seq" OWNED BY "pool_schema"."expenses"."expense_id";


--
-- TOC entry 288 (class 1259 OID 43167)
-- Name: facilities; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."facilities" (
    "facility_id" bigint NOT NULL,
    "name" character varying(255) NOT NULL,
    "capacity" integer,
    "status" character varying(255) DEFAULT 'active'::character varying NOT NULL,
    "type" character varying(255),
    "volume_liters" integer,
    "min_temperature" numeric(4,1),
    "max_temperature" numeric(4,1),
    "active" boolean DEFAULT true NOT NULL,
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone
);


ALTER TABLE "pool_schema"."facilities" OWNER TO "pooladmin";

--
-- TOC entry 287 (class 1259 OID 43166)
-- Name: facilities_facility_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."facilities_facility_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."facilities_facility_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5527 (class 0 OID 0)
-- Dependencies: 287
-- Name: facilities_facility_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."facilities_facility_id_seq" OWNED BY "pool_schema"."facilities"."facility_id";


--
-- TOC entry 238 (class 1259 OID 42657)
-- Name: failed_jobs; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."failed_jobs" (
    "id" bigint NOT NULL,
    "uuid" character varying(255) NOT NULL,
    "connection" "text" NOT NULL,
    "queue" "text" NOT NULL,
    "payload" "text" NOT NULL,
    "exception" "text" NOT NULL,
    "failed_at" timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE "pool_schema"."failed_jobs" OWNER TO "pooladmin";

--
-- TOC entry 237 (class 1259 OID 42656)
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."failed_jobs_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."failed_jobs_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5528 (class 0 OID 0)
-- Dependencies: 237
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."failed_jobs_id_seq" OWNED BY "pool_schema"."failed_jobs"."id";


--
-- TOC entry 236 (class 1259 OID 42642)
-- Name: job_batches; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."job_batches" (
    "id" character varying(255) NOT NULL,
    "name" character varying(255) NOT NULL,
    "total_jobs" integer NOT NULL,
    "pending_jobs" integer NOT NULL,
    "failed_jobs" integer NOT NULL,
    "failed_job_ids" "text" NOT NULL,
    "options" "text",
    "cancelled_at" integer,
    "created_at" integer NOT NULL,
    "finished_at" integer
);


ALTER TABLE "pool_schema"."job_batches" OWNER TO "pooladmin";

--
-- TOC entry 235 (class 1259 OID 42627)
-- Name: jobs; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."jobs" (
    "id" bigint NOT NULL,
    "queue" character varying(255) NOT NULL,
    "payload" "text" NOT NULL,
    "attempts" smallint NOT NULL,
    "reserved_at" integer,
    "available_at" integer NOT NULL,
    "created_at" integer NOT NULL
);


ALTER TABLE "pool_schema"."jobs" OWNER TO "pooladmin";

--
-- TOC entry 234 (class 1259 OID 42626)
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."jobs_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."jobs_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5529 (class 0 OID 0)
-- Dependencies: 234
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."jobs_id_seq" OWNED BY "pool_schema"."jobs"."id";


--
-- TOC entry 256 (class 1259 OID 42809)
-- Name: members; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."members" (
    "member_id" bigint NOT NULL,
    "first_name" character varying(255) NOT NULL,
    "last_name" character varying(255) NOT NULL,
    "email" character varying(255),
    "phone_number" character varying(255),
    "date_of_birth" "date",
    "address" character varying(255),
    "created_by" bigint,
    "updated_by" bigint,
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone,
    "photo_path" character varying(255),
    "emergency_contact_name" character varying(100),
    "emergency_contact_phone" character varying(20),
    "notes" "text",
    "health_conditions" "text"
);


ALTER TABLE "pool_schema"."members" OWNER TO "pooladmin";

--
-- TOC entry 255 (class 1259 OID 42808)
-- Name: members_member_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."members_member_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."members_member_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5530 (class 0 OID 0)
-- Dependencies: 255
-- Name: members_member_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."members_member_id_seq" OWNED BY "pool_schema"."members"."member_id";


--
-- TOC entry 227 (class 1259 OID 42561)
-- Name: migrations; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."migrations" (
    "id" integer NOT NULL,
    "migration" character varying(255) NOT NULL,
    "batch" integer NOT NULL
);


ALTER TABLE "pool_schema"."migrations" OWNER TO "pooladmin";

--
-- TOC entry 226 (class 1259 OID 42560)
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."migrations_id_seq"
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."migrations_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5531 (class 0 OID 0)
-- Dependencies: 226
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."migrations_id_seq" OWNED BY "pool_schema"."migrations"."id";


--
-- TOC entry 230 (class 1259 OID 42585)
-- Name: password_reset_tokens; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."password_reset_tokens" (
    "email" character varying(255) NOT NULL,
    "token" character varying(255) NOT NULL,
    "created_at" timestamp(0) without time zone
);


ALTER TABLE "pool_schema"."password_reset_tokens" OWNER TO "pooladmin";

--
-- TOC entry 260 (class 1259 OID 42875)
-- Name: payments; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."payments" (
    "payment_id" bigint NOT NULL,
    "subscription_id" bigint NOT NULL,
    "amount" numeric(8,2) NOT NULL,
    "payment_date" timestamp(0) without time zone NOT NULL,
    "payment_method" character varying(255),
    "received_by_staff_id" bigint,
    "notes" "text",
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone
);


ALTER TABLE "pool_schema"."payments" OWNER TO "pooladmin";

--
-- TOC entry 259 (class 1259 OID 42874)
-- Name: payments_payment_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."payments_payment_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."payments_payment_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5532 (class 0 OID 0)
-- Dependencies: 259
-- Name: payments_payment_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."payments_payment_id_seq" OWNED BY "pool_schema"."payments"."payment_id";


--
-- TOC entry 240 (class 1259 OID 42676)
-- Name: permissions; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."permissions" (
    "permission_id" bigint NOT NULL,
    "permission_name" character varying(255) NOT NULL
);


ALTER TABLE "pool_schema"."permissions" OWNER TO "pooladmin";

--
-- TOC entry 239 (class 1259 OID 42675)
-- Name: permissions_permission_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."permissions_permission_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."permissions_permission_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5533 (class 0 OID 0)
-- Dependencies: 239
-- Name: permissions_permission_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."permissions_permission_id_seq" OWNED BY "pool_schema"."permissions"."permission_id";


--
-- TOC entry 254 (class 1259 OID 42795)
-- Name: plans; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."plans" (
    "plan_id" bigint NOT NULL,
    "plan_name" character varying(255) NOT NULL,
    "description" "text",
    "price" numeric(8,2) NOT NULL,
    "plan_type" character varying(255),
    "visits_per_week" integer,
    "duration_months" integer,
    "is_active" boolean DEFAULT true NOT NULL
);


ALTER TABLE "pool_schema"."plans" OWNER TO "pooladmin";

--
-- TOC entry 253 (class 1259 OID 42794)
-- Name: plans_plan_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."plans_plan_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."plans_plan_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5534 (class 0 OID 0)
-- Dependencies: 253
-- Name: plans_plan_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."plans_plan_id_seq" OWNED BY "pool_schema"."plans"."plan_id";


--
-- TOC entry 294 (class 1259 OID 43219)
-- Name: pool_chemical_stock; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."pool_chemical_stock" (
    "chemical_id" bigint NOT NULL,
    "name" character varying(255) NOT NULL,
    "type" character varying(255) NOT NULL,
    "quantity_available" numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    "unit" character varying(255) NOT NULL,
    "minimum_threshold" numeric(8,2) DEFAULT '10'::numeric NOT NULL,
    "last_updated" timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone
);


ALTER TABLE "pool_schema"."pool_chemical_stock" OWNER TO "pooladmin";

--
-- TOC entry 293 (class 1259 OID 43218)
-- Name: pool_chemical_stock_chemical_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."pool_chemical_stock_chemical_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."pool_chemical_stock_chemical_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5535 (class 0 OID 0)
-- Dependencies: 293
-- Name: pool_chemical_stock_chemical_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."pool_chemical_stock_chemical_id_seq" OWNED BY "pool_schema"."pool_chemical_stock"."chemical_id";


--
-- TOC entry 296 (class 1259 OID 43238)
-- Name: pool_chemical_usage; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."pool_chemical_usage" (
    "usage_id" bigint NOT NULL,
    "chemical_id" bigint NOT NULL,
    "technician_id" bigint NOT NULL,
    "quantity_used" numeric(8,2) NOT NULL,
    "usage_date" timestamp(0) without time zone NOT NULL,
    "purpose" character varying(255),
    "related_test_id" bigint,
    "comments" "text",
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone
);


ALTER TABLE "pool_schema"."pool_chemical_usage" OWNER TO "pooladmin";

--
-- TOC entry 295 (class 1259 OID 43237)
-- Name: pool_chemical_usage_usage_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."pool_chemical_usage_usage_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."pool_chemical_usage_usage_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5536 (class 0 OID 0)
-- Dependencies: 295
-- Name: pool_chemical_usage_usage_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."pool_chemical_usage_usage_id_seq" OWNED BY "pool_schema"."pool_chemical_usage"."usage_id";


--
-- TOC entry 302 (class 1259 OID 43329)
-- Name: pool_daily_tasks; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."pool_daily_tasks" (
    "task_id" bigint NOT NULL,
    "technician_id" bigint NOT NULL,
    "pool_id" bigint NOT NULL,
    "task_date" "date" NOT NULL,
    "pump_status" character varying(255),
    "pressure_reading" numeric(5,2),
    "skimmer_cleaned" boolean DEFAULT false NOT NULL,
    "vacuum_done" boolean DEFAULT false NOT NULL,
    "drains_checked" boolean DEFAULT false NOT NULL,
    "lighting_checked" boolean DEFAULT false NOT NULL,
    "anomalies_comment" "text",
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone,
    "debris_removed" boolean DEFAULT false NOT NULL,
    "drain_covers_inspected" boolean DEFAULT false NOT NULL,
    "clarity_test_passed" boolean DEFAULT false NOT NULL,
    "custom_data" json,
    "template_id" bigint
);


ALTER TABLE "pool_schema"."pool_daily_tasks" OWNER TO "pooladmin";

--
-- TOC entry 301 (class 1259 OID 43328)
-- Name: pool_daily_tasks_task_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."pool_daily_tasks_task_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."pool_daily_tasks_task_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5537 (class 0 OID 0)
-- Dependencies: 301
-- Name: pool_daily_tasks_task_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."pool_daily_tasks_task_id_seq" OWNED BY "pool_schema"."pool_daily_tasks"."task_id";


--
-- TOC entry 290 (class 1259 OID 43182)
-- Name: pool_equipment; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."pool_equipment" (
    "equipment_id" bigint NOT NULL,
    "name" character varying(255) NOT NULL,
    "type" character varying(255) NOT NULL,
    "serial_number" character varying(255),
    "location" character varying(255),
    "install_date" "date",
    "status" character varying(255) DEFAULT 'operational'::character varying NOT NULL,
    "last_maintenance_date" "date",
    "next_due_date" "date",
    "notes" "text",
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone
);


ALTER TABLE "pool_schema"."pool_equipment" OWNER TO "pooladmin";

--
-- TOC entry 289 (class 1259 OID 43181)
-- Name: pool_equipment_equipment_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."pool_equipment_equipment_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."pool_equipment_equipment_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5538 (class 0 OID 0)
-- Dependencies: 289
-- Name: pool_equipment_equipment_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."pool_equipment_equipment_id_seq" OWNED BY "pool_schema"."pool_equipment"."equipment_id";


--
-- TOC entry 298 (class 1259 OID 43267)
-- Name: pool_equipment_maintenance; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."pool_equipment_maintenance" (
    "maintenance_id" bigint NOT NULL,
    "equipment_id" bigint NOT NULL,
    "technician_id" bigint NOT NULL,
    "task_type" character varying(255) NOT NULL,
    "status" character varying(255) DEFAULT 'scheduled'::character varying NOT NULL,
    "scheduled_date" "date" NOT NULL,
    "completed_date" "date",
    "description" "text",
    "used_parts" "text",
    "working_hours_spent" numeric(4,1),
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone
);


ALTER TABLE "pool_schema"."pool_equipment_maintenance" OWNER TO "pooladmin";

--
-- TOC entry 297 (class 1259 OID 43266)
-- Name: pool_equipment_maintenance_maintenance_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."pool_equipment_maintenance_maintenance_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."pool_equipment_maintenance_maintenance_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5539 (class 0 OID 0)
-- Dependencies: 297
-- Name: pool_equipment_maintenance_maintenance_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."pool_equipment_maintenance_maintenance_id_seq" OWNED BY "pool_schema"."pool_equipment_maintenance"."maintenance_id";


--
-- TOC entry 300 (class 1259 OID 43293)
-- Name: pool_incidents; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."pool_incidents" (
    "incident_id" bigint NOT NULL,
    "title" character varying(255) NOT NULL,
    "description" "text" NOT NULL,
    "severity" character varying(255) NOT NULL,
    "equipment_id" bigint,
    "pool_id" bigint,
    "created_by" bigint NOT NULL,
    "assigned_to" bigint,
    "status" character varying(255) DEFAULT 'open'::character varying NOT NULL,
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone
);


ALTER TABLE "pool_schema"."pool_incidents" OWNER TO "pooladmin";

--
-- TOC entry 299 (class 1259 OID 43292)
-- Name: pool_incidents_incident_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."pool_incidents_incident_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."pool_incidents_incident_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5540 (class 0 OID 0)
-- Dependencies: 299
-- Name: pool_incidents_incident_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."pool_incidents_incident_id_seq" OWNED BY "pool_schema"."pool_incidents"."incident_id";


--
-- TOC entry 306 (class 1259 OID 43404)
-- Name: pool_monthly_tasks; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."pool_monthly_tasks" (
    "monthly_task_id" bigint NOT NULL,
    "facility_id" bigint NOT NULL,
    "technician_id" bigint NOT NULL,
    "water_replacement_partial" boolean DEFAULT false NOT NULL,
    "full_system_inspection" boolean DEFAULT false NOT NULL,
    "chemical_dosing_calibration" boolean DEFAULT false NOT NULL,
    "notes" "text",
    "completed_at" timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    "custom_data" json,
    "template_id" bigint
);


ALTER TABLE "pool_schema"."pool_monthly_tasks" OWNER TO "pooladmin";

--
-- TOC entry 305 (class 1259 OID 43403)
-- Name: pool_monthly_tasks_monthly_task_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."pool_monthly_tasks_monthly_task_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."pool_monthly_tasks_monthly_task_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5541 (class 0 OID 0)
-- Dependencies: 305
-- Name: pool_monthly_tasks_monthly_task_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."pool_monthly_tasks_monthly_task_id_seq" OWNED BY "pool_schema"."pool_monthly_tasks"."monthly_task_id";


--
-- TOC entry 292 (class 1259 OID 43196)
-- Name: pool_water_tests; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."pool_water_tests" (
    "id" bigint NOT NULL,
    "test_date" timestamp(0) without time zone NOT NULL,
    "technician_id" bigint NOT NULL,
    "pool_id" bigint NOT NULL,
    "ph" numeric(4,2),
    "chlorine_free" numeric(4,2),
    "chlorine_total" numeric(4,2),
    "bromine" numeric(4,2),
    "alkalinity" integer,
    "hardness" integer,
    "salinity" integer,
    "turbidity" numeric(5,2),
    "temperature" numeric(4,1),
    "orp" integer,
    "comments" "text",
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone
);


ALTER TABLE "pool_schema"."pool_water_tests" OWNER TO "pooladmin";

--
-- TOC entry 291 (class 1259 OID 43195)
-- Name: pool_water_tests_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."pool_water_tests_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."pool_water_tests_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5542 (class 0 OID 0)
-- Dependencies: 291
-- Name: pool_water_tests_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."pool_water_tests_id_seq" OWNED BY "pool_schema"."pool_water_tests"."id";


--
-- TOC entry 304 (class 1259 OID 43360)
-- Name: pool_weekly_tasks; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."pool_weekly_tasks" (
    "id" bigint NOT NULL,
    "technician_id" bigint NOT NULL,
    "pool_id" bigint NOT NULL,
    "week_number" integer NOT NULL,
    "year" integer NOT NULL,
    "backwash_done" boolean DEFAULT false NOT NULL,
    "filter_cleaned" boolean DEFAULT false NOT NULL,
    "brushing_done" boolean DEFAULT false NOT NULL,
    "heater_checked" boolean DEFAULT false NOT NULL,
    "chemical_doser_checked" boolean DEFAULT false NOT NULL,
    "general_inspection_comment" "text",
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone,
    "fittings_retightened" boolean DEFAULT false NOT NULL,
    "heater_tested" boolean DEFAULT false NOT NULL,
    "custom_data" json,
    "template_id" bigint
);


ALTER TABLE "pool_schema"."pool_weekly_tasks" OWNER TO "pooladmin";

--
-- TOC entry 303 (class 1259 OID 43359)
-- Name: pool_weekly_tasks_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."pool_weekly_tasks_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."pool_weekly_tasks_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5543 (class 0 OID 0)
-- Dependencies: 303
-- Name: pool_weekly_tasks_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."pool_weekly_tasks_id_seq" OWNED BY "pool_schema"."pool_weekly_tasks"."id";


--
-- TOC entry 286 (class 1259 OID 43131)
-- Name: product_images; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."product_images" (
    "id" bigint NOT NULL,
    "product_id" bigint NOT NULL,
    "image_path" character varying(255) NOT NULL,
    "is_primary" boolean DEFAULT false NOT NULL,
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone
);


ALTER TABLE "pool_schema"."product_images" OWNER TO "pooladmin";

--
-- TOC entry 285 (class 1259 OID 43130)
-- Name: product_images_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."product_images_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."product_images_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5544 (class 0 OID 0)
-- Dependencies: 285
-- Name: product_images_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."product_images_id_seq" OWNED BY "pool_schema"."product_images"."id";


--
-- TOC entry 272 (class 1259 OID 42987)
-- Name: products; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."products" (
    "id" bigint NOT NULL,
    "category_id" bigint NOT NULL,
    "name" character varying(255) NOT NULL,
    "description" "text",
    "price" numeric(8,2) NOT NULL,
    "purchase_price" numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    "stock_quantity" integer DEFAULT 0 NOT NULL,
    "alert_threshold" integer DEFAULT 0 NOT NULL,
    "image_path" character varying(255),
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone
);


ALTER TABLE "pool_schema"."products" OWNER TO "pooladmin";

--
-- TOC entry 271 (class 1259 OID 42986)
-- Name: products_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."products_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."products_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5545 (class 0 OID 0)
-- Dependencies: 271
-- Name: products_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."products_id_seq" OWNED BY "pool_schema"."products"."id";


--
-- TOC entry 244 (class 1259 OID 42698)
-- Name: role_permissions; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."role_permissions" (
    "id" bigint NOT NULL,
    "role_id" bigint NOT NULL,
    "permission_id" bigint NOT NULL
);


ALTER TABLE "pool_schema"."role_permissions" OWNER TO "pooladmin";

--
-- TOC entry 243 (class 1259 OID 42697)
-- Name: role_permissions_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."role_permissions_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."role_permissions_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5546 (class 0 OID 0)
-- Dependencies: 243
-- Name: role_permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."role_permissions_id_seq" OWNED BY "pool_schema"."role_permissions"."id";


--
-- TOC entry 242 (class 1259 OID 42687)
-- Name: roles; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."roles" (
    "role_id" bigint NOT NULL,
    "role_name" character varying(255) NOT NULL
);


ALTER TABLE "pool_schema"."roles" OWNER TO "pooladmin";

--
-- TOC entry 241 (class 1259 OID 42686)
-- Name: roles_role_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."roles_role_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."roles_role_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5547 (class 0 OID 0)
-- Dependencies: 241
-- Name: roles_role_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."roles_role_id_seq" OWNED BY "pool_schema"."roles"."role_id";


--
-- TOC entry 276 (class 1259 OID 43032)
-- Name: sale_items; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."sale_items" (
    "id" bigint NOT NULL,
    "sale_id" bigint NOT NULL,
    "product_id" bigint NOT NULL,
    "quantity" integer NOT NULL,
    "unit_price" numeric(8,2) NOT NULL,
    "subtotal" numeric(8,2) NOT NULL,
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone
);


ALTER TABLE "pool_schema"."sale_items" OWNER TO "pooladmin";

--
-- TOC entry 275 (class 1259 OID 43031)
-- Name: sale_items_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."sale_items_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."sale_items_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5548 (class 0 OID 0)
-- Dependencies: 275
-- Name: sale_items_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."sale_items_id_seq" OWNED BY "pool_schema"."sale_items"."id";


--
-- TOC entry 274 (class 1259 OID 43011)
-- Name: sales; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."sales" (
    "id" bigint NOT NULL,
    "staff_id" bigint NOT NULL,
    "member_id" bigint,
    "total_amount" numeric(8,2) NOT NULL,
    "payment_method" character varying(255) NOT NULL,
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone
);


ALTER TABLE "pool_schema"."sales" OWNER TO "pooladmin";

--
-- TOC entry 273 (class 1259 OID 43010)
-- Name: sales_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."sales_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."sales_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5549 (class 0 OID 0)
-- Dependencies: 273
-- Name: sales_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."sales_id_seq" OWNED BY "pool_schema"."sales"."id";


--
-- TOC entry 231 (class 1259 OID 42594)
-- Name: sessions; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."sessions" (
    "id" character varying(255) NOT NULL,
    "user_id" bigint,
    "ip_address" character varying(45),
    "user_agent" "text",
    "payload" "text" NOT NULL,
    "last_activity" integer NOT NULL
);


ALTER TABLE "pool_schema"."sessions" OWNER TO "pooladmin";

--
-- TOC entry 246 (class 1259 OID 42718)
-- Name: staff; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."staff" (
    "staff_id" bigint NOT NULL,
    "first_name" character varying(255) NOT NULL,
    "last_name" character varying(255) NOT NULL,
    "username" character varying(255) NOT NULL,
    "password_hash" character varying(255) NOT NULL,
    "role_id" bigint,
    "is_active" boolean DEFAULT true NOT NULL,
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone,
    "phone_number" character varying(255),
    "email" character varying(255),
    "specialty" character varying(255),
    "hiring_date" "date",
    "salary_type" character varying(255) DEFAULT 'per_hour'::character varying NOT NULL,
    "hourly_rate" numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    "notes" "text"
);


ALTER TABLE "pool_schema"."staff" OWNER TO "pooladmin";

--
-- TOC entry 280 (class 1259 OID 43076)
-- Name: staff_leaves; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."staff_leaves" (
    "id" bigint NOT NULL,
    "staff_id" integer NOT NULL,
    "start_date" "date" NOT NULL,
    "end_date" "date" NOT NULL,
    "type" character varying(255) NOT NULL,
    "status" character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    "reason" "text",
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone
);


ALTER TABLE "pool_schema"."staff_leaves" OWNER TO "pooladmin";

--
-- TOC entry 279 (class 1259 OID 43075)
-- Name: staff_leaves_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."staff_leaves_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."staff_leaves_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5550 (class 0 OID 0)
-- Dependencies: 279
-- Name: staff_leaves_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."staff_leaves_id_seq" OWNED BY "pool_schema"."staff_leaves"."id";


--
-- TOC entry 278 (class 1259 OID 43055)
-- Name: staff_schedules; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."staff_schedules" (
    "id" bigint NOT NULL,
    "staff_id" integer NOT NULL,
    "date" "date" NOT NULL,
    "start_time" time(0) without time zone NOT NULL,
    "end_time" time(0) without time zone NOT NULL,
    "type" character varying(255) DEFAULT 'work'::character varying NOT NULL,
    "notes" "text",
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone
);


ALTER TABLE "pool_schema"."staff_schedules" OWNER TO "pooladmin";

--
-- TOC entry 277 (class 1259 OID 43054)
-- Name: staff_schedules_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."staff_schedules_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."staff_schedules_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5551 (class 0 OID 0)
-- Dependencies: 277
-- Name: staff_schedules_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."staff_schedules_id_seq" OWNED BY "pool_schema"."staff_schedules"."id";


--
-- TOC entry 245 (class 1259 OID 42717)
-- Name: staff_staff_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."staff_staff_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."staff_staff_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5552 (class 0 OID 0)
-- Dependencies: 245
-- Name: staff_staff_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."staff_staff_id_seq" OWNED BY "pool_schema"."staff"."staff_id";


--
-- TOC entry 266 (class 1259 OID 42935)
-- Name: subscription_allowed_days; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."subscription_allowed_days" (
    "id" bigint NOT NULL,
    "subscription_id" bigint NOT NULL,
    "weekday_id" bigint NOT NULL
);


ALTER TABLE "pool_schema"."subscription_allowed_days" OWNER TO "pooladmin";

--
-- TOC entry 265 (class 1259 OID 42934)
-- Name: subscription_allowed_days_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."subscription_allowed_days_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."subscription_allowed_days_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5553 (class 0 OID 0)
-- Dependencies: 265
-- Name: subscription_allowed_days_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."subscription_allowed_days_id_seq" OWNED BY "pool_schema"."subscription_allowed_days"."id";


--
-- TOC entry 258 (class 1259 OID 42831)
-- Name: subscriptions; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."subscriptions" (
    "subscription_id" bigint NOT NULL,
    "member_id" bigint NOT NULL,
    "plan_id" bigint NOT NULL,
    "start_date" "date" NOT NULL,
    "end_date" "date" NOT NULL,
    "status" character varying(255) DEFAULT 'active'::character varying NOT NULL,
    "paused_at" timestamp(0) without time zone,
    "resumes_at" "date",
    "visits_per_week" integer,
    "deactivated_by" bigint,
    "created_by" bigint,
    "updated_by" bigint,
    "activity_id" bigint,
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone
);


ALTER TABLE "pool_schema"."subscriptions" OWNER TO "pooladmin";

--
-- TOC entry 257 (class 1259 OID 42830)
-- Name: subscriptions_subscription_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."subscriptions_subscription_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."subscriptions_subscription_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5554 (class 0 OID 0)
-- Dependencies: 257
-- Name: subscriptions_subscription_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."subscriptions_subscription_id_seq" OWNED BY "pool_schema"."subscriptions"."subscription_id";


--
-- TOC entry 308 (class 1259 OID 43434)
-- Name: task_templates; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."task_templates" (
    "id" bigint NOT NULL,
    "name" character varying(255) NOT NULL,
    "type" character varying(255) NOT NULL,
    "items" json NOT NULL,
    "is_active" boolean DEFAULT true NOT NULL,
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone,
    CONSTRAINT "task_templates_type_check" CHECK ((("type")::"text" = ANY ((ARRAY['daily'::character varying, 'weekly'::character varying, 'monthly'::character varying])::"text"[])))
);


ALTER TABLE "pool_schema"."task_templates" OWNER TO "pooladmin";

--
-- TOC entry 307 (class 1259 OID 43433)
-- Name: task_templates_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."task_templates_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."task_templates_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5555 (class 0 OID 0)
-- Dependencies: 307
-- Name: task_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."task_templates_id_seq" OWNED BY "pool_schema"."task_templates"."id";


--
-- TOC entry 252 (class 1259 OID 42765)
-- Name: time_slots; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."time_slots" (
    "slot_id" bigint NOT NULL,
    "weekday_id" bigint NOT NULL,
    "start_time" time(0) without time zone NOT NULL,
    "end_time" time(0) without time zone NOT NULL,
    "activity_id" bigint,
    "assigned_group" character varying(255),
    "is_blocked" boolean DEFAULT false NOT NULL,
    "notes" "text",
    "created_by" bigint,
    "capacity" integer,
    "coach_id" bigint,
    "assistant_coach_id" bigint
);


ALTER TABLE "pool_schema"."time_slots" OWNER TO "pooladmin";

--
-- TOC entry 251 (class 1259 OID 42764)
-- Name: time_slots_slot_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."time_slots_slot_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."time_slots_slot_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5556 (class 0 OID 0)
-- Dependencies: 251
-- Name: time_slots_slot_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."time_slots_slot_id_seq" OWNED BY "pool_schema"."time_slots"."slot_id";


--
-- TOC entry 229 (class 1259 OID 42571)
-- Name: users; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."users" (
    "id" bigint NOT NULL,
    "name" character varying(255) NOT NULL,
    "email" character varying(255) NOT NULL,
    "email_verified_at" timestamp(0) without time zone,
    "password" character varying(255) NOT NULL,
    "remember_token" character varying(100),
    "created_at" timestamp(0) without time zone,
    "updated_at" timestamp(0) without time zone,
    "role_id" bigint
);


ALTER TABLE "pool_schema"."users" OWNER TO "pooladmin";

--
-- TOC entry 228 (class 1259 OID 42570)
-- Name: users_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."users_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."users_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5557 (class 0 OID 0)
-- Dependencies: 228
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."users_id_seq" OWNED BY "pool_schema"."users"."id";


--
-- TOC entry 248 (class 1259 OID 42741)
-- Name: weekdays; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE "pool_schema"."weekdays" (
    "weekday_id" bigint NOT NULL,
    "day_name" character varying(255) NOT NULL
);


ALTER TABLE "pool_schema"."weekdays" OWNER TO "pooladmin";

--
-- TOC entry 247 (class 1259 OID 42740)
-- Name: weekdays_weekday_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE "pool_schema"."weekdays_weekday_id_seq"
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE "pool_schema"."weekdays_weekday_id_seq" OWNER TO "pooladmin";

--
-- TOC entry 5558 (class 0 OID 0)
-- Dependencies: 247
-- Name: weekdays_weekday_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."weekdays_weekday_id_seq" OWNED BY "pool_schema"."weekdays"."weekday_id";


--
-- TOC entry 5132 (class 2604 OID 42901)
-- Name: access_badges badge_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."access_badges" ALTER COLUMN "badge_id" SET DEFAULT "nextval"('"pool_schema"."access_badges_badge_id_seq"'::"regclass");


--
-- TOC entry 5134 (class 2604 OID 42921)
-- Name: access_logs log_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."access_logs" ALTER COLUMN "log_id" SET DEFAULT "nextval"('"pool_schema"."access_logs_log_id_seq"'::"regclass");


--
-- TOC entry 5122 (class 2604 OID 42755)
-- Name: activities activity_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."activities" ALTER COLUMN "activity_id" SET DEFAULT "nextval"('"pool_schema"."activities_activity_id_seq"'::"regclass");


--
-- TOC entry 5136 (class 2604 OID 42958)
-- Name: activity_plan_prices id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."activity_plan_prices" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."activity_plan_prices_id_seq"'::"regclass");


--
-- TOC entry 5197 (class 2604 OID 43519)
-- Name: backup_jobs id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."backup_jobs" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."backup_jobs_id_seq"'::"regclass");


--
-- TOC entry 5191 (class 2604 OID 43499)
-- Name: backup_settings id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."backup_settings" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."backup_settings_id_seq"'::"regclass");


--
-- TOC entry 5137 (class 2604 OID 42979)
-- Name: categories id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."categories" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."categories_id_seq"'::"regclass");


--
-- TOC entry 5150 (class 2604 OID 43126)
-- Name: coach_time_slot id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."coach_time_slot" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."coach_time_slot_id_seq"'::"regclass");


--
-- TOC entry 5148 (class 2604 OID 43100)
-- Name: expenses expense_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."expenses" ALTER COLUMN "expense_id" SET DEFAULT "nextval"('"pool_schema"."expenses_expense_id_seq"'::"regclass");


--
-- TOC entry 5153 (class 2604 OID 43170)
-- Name: facilities facility_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."facilities" ALTER COLUMN "facility_id" SET DEFAULT "nextval"('"pool_schema"."facilities_facility_id_seq"'::"regclass");


--
-- TOC entry 5112 (class 2604 OID 42660)
-- Name: failed_jobs id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."failed_jobs" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."failed_jobs_id_seq"'::"regclass");


--
-- TOC entry 5111 (class 2604 OID 42630)
-- Name: jobs id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."jobs" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."jobs_id_seq"'::"regclass");


--
-- TOC entry 5128 (class 2604 OID 42812)
-- Name: members member_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."members" ALTER COLUMN "member_id" SET DEFAULT "nextval"('"pool_schema"."members_member_id_seq"'::"regclass");


--
-- TOC entry 5109 (class 2604 OID 42564)
-- Name: migrations id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."migrations" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."migrations_id_seq"'::"regclass");


--
-- TOC entry 5131 (class 2604 OID 42878)
-- Name: payments payment_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."payments" ALTER COLUMN "payment_id" SET DEFAULT "nextval"('"pool_schema"."payments_payment_id_seq"'::"regclass");


--
-- TOC entry 5114 (class 2604 OID 42679)
-- Name: permissions permission_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."permissions" ALTER COLUMN "permission_id" SET DEFAULT "nextval"('"pool_schema"."permissions_permission_id_seq"'::"regclass");


--
-- TOC entry 5126 (class 2604 OID 42798)
-- Name: plans plan_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."plans" ALTER COLUMN "plan_id" SET DEFAULT "nextval"('"pool_schema"."plans_plan_id_seq"'::"regclass");


--
-- TOC entry 5159 (class 2604 OID 43222)
-- Name: pool_chemical_stock chemical_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_chemical_stock" ALTER COLUMN "chemical_id" SET DEFAULT "nextval"('"pool_schema"."pool_chemical_stock_chemical_id_seq"'::"regclass");


--
-- TOC entry 5163 (class 2604 OID 43241)
-- Name: pool_chemical_usage usage_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_chemical_usage" ALTER COLUMN "usage_id" SET DEFAULT "nextval"('"pool_schema"."pool_chemical_usage_usage_id_seq"'::"regclass");


--
-- TOC entry 5168 (class 2604 OID 43332)
-- Name: pool_daily_tasks task_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_daily_tasks" ALTER COLUMN "task_id" SET DEFAULT "nextval"('"pool_schema"."pool_daily_tasks_task_id_seq"'::"regclass");


--
-- TOC entry 5156 (class 2604 OID 43185)
-- Name: pool_equipment equipment_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_equipment" ALTER COLUMN "equipment_id" SET DEFAULT "nextval"('"pool_schema"."pool_equipment_equipment_id_seq"'::"regclass");


--
-- TOC entry 5164 (class 2604 OID 43270)
-- Name: pool_equipment_maintenance maintenance_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_equipment_maintenance" ALTER COLUMN "maintenance_id" SET DEFAULT "nextval"('"pool_schema"."pool_equipment_maintenance_maintenance_id_seq"'::"regclass");


--
-- TOC entry 5166 (class 2604 OID 43296)
-- Name: pool_incidents incident_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_incidents" ALTER COLUMN "incident_id" SET DEFAULT "nextval"('"pool_schema"."pool_incidents_incident_id_seq"'::"regclass");


--
-- TOC entry 5184 (class 2604 OID 43407)
-- Name: pool_monthly_tasks monthly_task_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_monthly_tasks" ALTER COLUMN "monthly_task_id" SET DEFAULT "nextval"('"pool_schema"."pool_monthly_tasks_monthly_task_id_seq"'::"regclass");


--
-- TOC entry 5158 (class 2604 OID 43199)
-- Name: pool_water_tests id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_water_tests" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."pool_water_tests_id_seq"'::"regclass");


--
-- TOC entry 5176 (class 2604 OID 43363)
-- Name: pool_weekly_tasks id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_weekly_tasks" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."pool_weekly_tasks_id_seq"'::"regclass");


--
-- TOC entry 5151 (class 2604 OID 43134)
-- Name: product_images id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."product_images" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."product_images_id_seq"'::"regclass");


--
-- TOC entry 5138 (class 2604 OID 42990)
-- Name: products id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."products" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."products_id_seq"'::"regclass");


--
-- TOC entry 5116 (class 2604 OID 42701)
-- Name: role_permissions id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."role_permissions" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."role_permissions_id_seq"'::"regclass");


--
-- TOC entry 5115 (class 2604 OID 42690)
-- Name: roles role_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."roles" ALTER COLUMN "role_id" SET DEFAULT "nextval"('"pool_schema"."roles_role_id_seq"'::"regclass");


--
-- TOC entry 5143 (class 2604 OID 43035)
-- Name: sale_items id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."sale_items" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."sale_items_id_seq"'::"regclass");


--
-- TOC entry 5142 (class 2604 OID 43014)
-- Name: sales id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."sales" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."sales_id_seq"'::"regclass");


--
-- TOC entry 5117 (class 2604 OID 42721)
-- Name: staff staff_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."staff" ALTER COLUMN "staff_id" SET DEFAULT "nextval"('"pool_schema"."staff_staff_id_seq"'::"regclass");


--
-- TOC entry 5146 (class 2604 OID 43079)
-- Name: staff_leaves id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."staff_leaves" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."staff_leaves_id_seq"'::"regclass");


--
-- TOC entry 5144 (class 2604 OID 43058)
-- Name: staff_schedules id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."staff_schedules" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."staff_schedules_id_seq"'::"regclass");


--
-- TOC entry 5135 (class 2604 OID 42938)
-- Name: subscription_allowed_days id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscription_allowed_days" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."subscription_allowed_days_id_seq"'::"regclass");


--
-- TOC entry 5129 (class 2604 OID 42834)
-- Name: subscriptions subscription_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscriptions" ALTER COLUMN "subscription_id" SET DEFAULT "nextval"('"pool_schema"."subscriptions_subscription_id_seq"'::"regclass");


--
-- TOC entry 5189 (class 2604 OID 43437)
-- Name: task_templates id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."task_templates" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."task_templates_id_seq"'::"regclass");


--
-- TOC entry 5124 (class 2604 OID 42768)
-- Name: time_slots slot_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."time_slots" ALTER COLUMN "slot_id" SET DEFAULT "nextval"('"pool_schema"."time_slots_slot_id_seq"'::"regclass");


--
-- TOC entry 5110 (class 2604 OID 42574)
-- Name: users id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."users" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."users_id_seq"'::"regclass");


--
-- TOC entry 5121 (class 2604 OID 42744)
-- Name: weekdays weekday_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."weekdays" ALTER COLUMN "weekday_id" SET DEFAULT "nextval"('"pool_schema"."weekdays_weekday_id_seq"'::"regclass");


--
-- TOC entry 5256 (class 2606 OID 42909)
-- Name: access_badges access_badges_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."access_badges"
    ADD CONSTRAINT "access_badges_pkey" PRIMARY KEY ("badge_id");


--
-- TOC entry 5260 (class 2606 OID 42928)
-- Name: access_logs access_logs_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."access_logs"
    ADD CONSTRAINT "access_logs_pkey" PRIMARY KEY ("log_id");


--
-- TOC entry 5244 (class 2606 OID 42763)
-- Name: activities activities_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."activities"
    ADD CONSTRAINT "activities_pkey" PRIMARY KEY ("activity_id");


--
-- TOC entry 5264 (class 2606 OID 42964)
-- Name: activity_plan_prices activity_plan_prices_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."activity_plan_prices"
    ADD CONSTRAINT "activity_plan_prices_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5308 (class 2606 OID 43529)
-- Name: backup_jobs backup_jobs_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."backup_jobs"
    ADD CONSTRAINT "backup_jobs_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5306 (class 2606 OID 43514)
-- Name: backup_settings backup_settings_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."backup_settings"
    ADD CONSTRAINT "backup_settings_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5215 (class 2606 OID 42625)
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."cache_locks"
    ADD CONSTRAINT "cache_locks_pkey" PRIMARY KEY ("key");


--
-- TOC entry 5213 (class 2606 OID 42615)
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."cache"
    ADD CONSTRAINT "cache_pkey" PRIMARY KEY ("key");


--
-- TOC entry 5266 (class 2606 OID 42985)
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."categories"
    ADD CONSTRAINT "categories_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5280 (class 2606 OID 43129)
-- Name: coach_time_slot coach_time_slot_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."coach_time_slot"
    ADD CONSTRAINT "coach_time_slot_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5278 (class 2606 OID 43111)
-- Name: expenses expenses_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."expenses"
    ADD CONSTRAINT "expenses_pkey" PRIMARY KEY ("expense_id");


--
-- TOC entry 5284 (class 2606 OID 43180)
-- Name: facilities facilities_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."facilities"
    ADD CONSTRAINT "facilities_pkey" PRIMARY KEY ("facility_id");


--
-- TOC entry 5222 (class 2606 OID 42672)
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."failed_jobs"
    ADD CONSTRAINT "failed_jobs_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5224 (class 2606 OID 42674)
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."failed_jobs"
    ADD CONSTRAINT "failed_jobs_uuid_unique" UNIQUE ("uuid");


--
-- TOC entry 5220 (class 2606 OID 42655)
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."job_batches"
    ADD CONSTRAINT "job_batches_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5217 (class 2606 OID 42640)
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."jobs"
    ADD CONSTRAINT "jobs_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5250 (class 2606 OID 42819)
-- Name: members members_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."members"
    ADD CONSTRAINT "members_pkey" PRIMARY KEY ("member_id");


--
-- TOC entry 5201 (class 2606 OID 42569)
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."migrations"
    ADD CONSTRAINT "migrations_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5207 (class 2606 OID 42593)
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."password_reset_tokens"
    ADD CONSTRAINT "password_reset_tokens_pkey" PRIMARY KEY ("email");


--
-- TOC entry 5254 (class 2606 OID 42886)
-- Name: payments payments_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."payments"
    ADD CONSTRAINT "payments_pkey" PRIMARY KEY ("payment_id");


--
-- TOC entry 5226 (class 2606 OID 42683)
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."permissions"
    ADD CONSTRAINT "permissions_pkey" PRIMARY KEY ("permission_id");


--
-- TOC entry 5248 (class 2606 OID 42807)
-- Name: plans plans_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."plans"
    ADD CONSTRAINT "plans_pkey" PRIMARY KEY ("plan_id");


--
-- TOC entry 5290 (class 2606 OID 43236)
-- Name: pool_chemical_stock pool_chemical_stock_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_chemical_stock"
    ADD CONSTRAINT "pool_chemical_stock_pkey" PRIMARY KEY ("chemical_id");


--
-- TOC entry 5292 (class 2606 OID 43250)
-- Name: pool_chemical_usage pool_chemical_usage_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_chemical_usage"
    ADD CONSTRAINT "pool_chemical_usage_pkey" PRIMARY KEY ("usage_id");


--
-- TOC entry 5298 (class 2606 OID 43348)
-- Name: pool_daily_tasks pool_daily_tasks_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_daily_tasks"
    ADD CONSTRAINT "pool_daily_tasks_pkey" PRIMARY KEY ("task_id");


--
-- TOC entry 5294 (class 2606 OID 43281)
-- Name: pool_equipment_maintenance pool_equipment_maintenance_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_equipment_maintenance"
    ADD CONSTRAINT "pool_equipment_maintenance_pkey" PRIMARY KEY ("maintenance_id");


--
-- TOC entry 5286 (class 2606 OID 43194)
-- Name: pool_equipment pool_equipment_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_equipment"
    ADD CONSTRAINT "pool_equipment_pkey" PRIMARY KEY ("equipment_id");


--
-- TOC entry 5296 (class 2606 OID 43307)
-- Name: pool_incidents pool_incidents_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_incidents"
    ADD CONSTRAINT "pool_incidents_pkey" PRIMARY KEY ("incident_id");


--
-- TOC entry 5302 (class 2606 OID 43422)
-- Name: pool_monthly_tasks pool_monthly_tasks_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_monthly_tasks"
    ADD CONSTRAINT "pool_monthly_tasks_pkey" PRIMARY KEY ("monthly_task_id");


--
-- TOC entry 5258 (class 2606 OID 42916)
-- Name: access_badges pool_schema_access_badges_badge_uid_unique; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."access_badges"
    ADD CONSTRAINT "pool_schema_access_badges_badge_uid_unique" UNIQUE ("badge_uid");


--
-- TOC entry 5228 (class 2606 OID 42685)
-- Name: permissions pool_schema_permissions_permission_name_unique; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."permissions"
    ADD CONSTRAINT "pool_schema_permissions_permission_name_unique" UNIQUE ("permission_name");


--
-- TOC entry 5230 (class 2606 OID 42696)
-- Name: roles pool_schema_roles_role_name_unique; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."roles"
    ADD CONSTRAINT "pool_schema_roles_role_name_unique" UNIQUE ("role_name");


--
-- TOC entry 5236 (class 2606 OID 42739)
-- Name: staff pool_schema_staff_username_unique; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."staff"
    ADD CONSTRAINT "pool_schema_staff_username_unique" UNIQUE ("username");


--
-- TOC entry 5240 (class 2606 OID 42750)
-- Name: weekdays pool_schema_weekdays_day_name_unique; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."weekdays"
    ADD CONSTRAINT "pool_schema_weekdays_day_name_unique" UNIQUE ("day_name");


--
-- TOC entry 5288 (class 2606 OID 43207)
-- Name: pool_water_tests pool_water_tests_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_water_tests"
    ADD CONSTRAINT "pool_water_tests_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5300 (class 2606 OID 43382)
-- Name: pool_weekly_tasks pool_weekly_tasks_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_weekly_tasks"
    ADD CONSTRAINT "pool_weekly_tasks_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5282 (class 2606 OID 43141)
-- Name: product_images product_images_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."product_images"
    ADD CONSTRAINT "product_images_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5268 (class 2606 OID 43004)
-- Name: products products_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."products"
    ADD CONSTRAINT "products_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5234 (class 2606 OID 42706)
-- Name: role_permissions role_permissions_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."role_permissions"
    ADD CONSTRAINT "role_permissions_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5232 (class 2606 OID 42694)
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."roles"
    ADD CONSTRAINT "roles_pkey" PRIMARY KEY ("role_id");


--
-- TOC entry 5272 (class 2606 OID 43043)
-- Name: sale_items sale_items_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."sale_items"
    ADD CONSTRAINT "sale_items_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5270 (class 2606 OID 43020)
-- Name: sales sales_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."sales"
    ADD CONSTRAINT "sales_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5210 (class 2606 OID 42603)
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."sessions"
    ADD CONSTRAINT "sessions_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5276 (class 2606 OID 43090)
-- Name: staff_leaves staff_leaves_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."staff_leaves"
    ADD CONSTRAINT "staff_leaves_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5238 (class 2606 OID 42732)
-- Name: staff staff_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."staff"
    ADD CONSTRAINT "staff_pkey" PRIMARY KEY ("staff_id");


--
-- TOC entry 5274 (class 2606 OID 43069)
-- Name: staff_schedules staff_schedules_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."staff_schedules"
    ADD CONSTRAINT "staff_schedules_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5262 (class 2606 OID 42943)
-- Name: subscription_allowed_days subscription_allowed_days_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscription_allowed_days"
    ADD CONSTRAINT "subscription_allowed_days_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5252 (class 2606 OID 42843)
-- Name: subscriptions subscriptions_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscriptions"
    ADD CONSTRAINT "subscriptions_pkey" PRIMARY KEY ("subscription_id");


--
-- TOC entry 5304 (class 2606 OID 43448)
-- Name: task_templates task_templates_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."task_templates"
    ADD CONSTRAINT "task_templates_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5246 (class 2606 OID 42778)
-- Name: time_slots time_slots_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."time_slots"
    ADD CONSTRAINT "time_slots_pkey" PRIMARY KEY ("slot_id");


--
-- TOC entry 5203 (class 2606 OID 42584)
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."users"
    ADD CONSTRAINT "users_email_unique" UNIQUE ("email");


--
-- TOC entry 5205 (class 2606 OID 42582)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."users"
    ADD CONSTRAINT "users_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5242 (class 2606 OID 42748)
-- Name: weekdays weekdays_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."weekdays"
    ADD CONSTRAINT "weekdays_pkey" PRIMARY KEY ("weekday_id");


--
-- TOC entry 5218 (class 1259 OID 42641)
-- Name: jobs_queue_index; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX "jobs_queue_index" ON "pool_schema"."jobs" USING "btree" ("queue");


--
-- TOC entry 5208 (class 1259 OID 42605)
-- Name: sessions_last_activity_index; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX "sessions_last_activity_index" ON "pool_schema"."sessions" USING "btree" ("last_activity");


--
-- TOC entry 5211 (class 1259 OID 42604)
-- Name: sessions_user_id_index; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX "sessions_user_id_index" ON "pool_schema"."sessions" USING "btree" ("user_id");


--
-- TOC entry 5327 (class 2606 OID 42910)
-- Name: access_badges pool_schema_access_badges_member_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."access_badges"
    ADD CONSTRAINT "pool_schema_access_badges_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "pool_schema"."members"("member_id") ON DELETE CASCADE;


--
-- TOC entry 5328 (class 2606 OID 43156)
-- Name: access_badges pool_schema_access_badges_staff_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."access_badges"
    ADD CONSTRAINT "pool_schema_access_badges_staff_id_foreign" FOREIGN KEY ("staff_id") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE CASCADE;


--
-- TOC entry 5329 (class 2606 OID 42929)
-- Name: access_logs pool_schema_access_logs_member_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."access_logs"
    ADD CONSTRAINT "pool_schema_access_logs_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "pool_schema"."members"("member_id") ON DELETE CASCADE;


--
-- TOC entry 5330 (class 2606 OID 43161)
-- Name: access_logs pool_schema_access_logs_staff_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."access_logs"
    ADD CONSTRAINT "pool_schema_access_logs_staff_id_foreign" FOREIGN KEY ("staff_id") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE CASCADE;


--
-- TOC entry 5333 (class 2606 OID 42965)
-- Name: activity_plan_prices pool_schema_activity_plan_prices_activity_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."activity_plan_prices"
    ADD CONSTRAINT "pool_schema_activity_plan_prices_activity_id_foreign" FOREIGN KEY ("activity_id") REFERENCES "pool_schema"."activities"("activity_id") ON DELETE CASCADE;


--
-- TOC entry 5334 (class 2606 OID 42970)
-- Name: activity_plan_prices pool_schema_activity_plan_prices_plan_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."activity_plan_prices"
    ADD CONSTRAINT "pool_schema_activity_plan_prices_plan_id_foreign" FOREIGN KEY ("plan_id") REFERENCES "pool_schema"."plans"("plan_id") ON DELETE CASCADE;


--
-- TOC entry 5364 (class 2606 OID 43530)
-- Name: backup_jobs pool_schema_backup_jobs_triggered_by_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."backup_jobs"
    ADD CONSTRAINT "pool_schema_backup_jobs_triggered_by_foreign" FOREIGN KEY ("triggered_by") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE SET NULL;


--
-- TOC entry 5342 (class 2606 OID 43112)
-- Name: expenses pool_schema_expenses_created_by_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."expenses"
    ADD CONSTRAINT "pool_schema_expenses_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE SET NULL;


--
-- TOC entry 5317 (class 2606 OID 42820)
-- Name: members pool_schema_members_created_by_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."members"
    ADD CONSTRAINT "pool_schema_members_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE SET NULL;


--
-- TOC entry 5318 (class 2606 OID 42825)
-- Name: members pool_schema_members_updated_by_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."members"
    ADD CONSTRAINT "pool_schema_members_updated_by_foreign" FOREIGN KEY ("updated_by") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE SET NULL;


--
-- TOC entry 5325 (class 2606 OID 42892)
-- Name: payments pool_schema_payments_received_by_staff_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."payments"
    ADD CONSTRAINT "pool_schema_payments_received_by_staff_id_foreign" FOREIGN KEY ("received_by_staff_id") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE SET NULL;


--
-- TOC entry 5326 (class 2606 OID 42887)
-- Name: payments pool_schema_payments_subscription_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."payments"
    ADD CONSTRAINT "pool_schema_payments_subscription_id_foreign" FOREIGN KEY ("subscription_id") REFERENCES "pool_schema"."subscriptions"("subscription_id") ON DELETE CASCADE;


--
-- TOC entry 5346 (class 2606 OID 43251)
-- Name: pool_chemical_usage pool_schema_pool_chemical_usage_chemical_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_chemical_usage"
    ADD CONSTRAINT "pool_schema_pool_chemical_usage_chemical_id_foreign" FOREIGN KEY ("chemical_id") REFERENCES "pool_schema"."pool_chemical_stock"("chemical_id");


--
-- TOC entry 5347 (class 2606 OID 43261)
-- Name: pool_chemical_usage pool_schema_pool_chemical_usage_related_test_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_chemical_usage"
    ADD CONSTRAINT "pool_schema_pool_chemical_usage_related_test_id_foreign" FOREIGN KEY ("related_test_id") REFERENCES "pool_schema"."pool_water_tests"("id") ON DELETE SET NULL;


--
-- TOC entry 5348 (class 2606 OID 43256)
-- Name: pool_chemical_usage pool_schema_pool_chemical_usage_technician_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_chemical_usage"
    ADD CONSTRAINT "pool_schema_pool_chemical_usage_technician_id_foreign" FOREIGN KEY ("technician_id") REFERENCES "pool_schema"."staff"("staff_id");


--
-- TOC entry 5355 (class 2606 OID 43354)
-- Name: pool_daily_tasks pool_schema_pool_daily_tasks_pool_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_daily_tasks"
    ADD CONSTRAINT "pool_schema_pool_daily_tasks_pool_id_foreign" FOREIGN KEY ("pool_id") REFERENCES "pool_schema"."facilities"("facility_id");


--
-- TOC entry 5356 (class 2606 OID 43349)
-- Name: pool_daily_tasks pool_schema_pool_daily_tasks_technician_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_daily_tasks"
    ADD CONSTRAINT "pool_schema_pool_daily_tasks_technician_id_foreign" FOREIGN KEY ("technician_id") REFERENCES "pool_schema"."staff"("staff_id");


--
-- TOC entry 5357 (class 2606 OID 43449)
-- Name: pool_daily_tasks pool_schema_pool_daily_tasks_template_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_daily_tasks"
    ADD CONSTRAINT "pool_schema_pool_daily_tasks_template_id_foreign" FOREIGN KEY ("template_id") REFERENCES "pool_schema"."task_templates"("id") ON DELETE SET NULL;


--
-- TOC entry 5349 (class 2606 OID 43282)
-- Name: pool_equipment_maintenance pool_schema_pool_equipment_maintenance_equipment_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_equipment_maintenance"
    ADD CONSTRAINT "pool_schema_pool_equipment_maintenance_equipment_id_foreign" FOREIGN KEY ("equipment_id") REFERENCES "pool_schema"."pool_equipment"("equipment_id");


--
-- TOC entry 5350 (class 2606 OID 43287)
-- Name: pool_equipment_maintenance pool_schema_pool_equipment_maintenance_technician_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_equipment_maintenance"
    ADD CONSTRAINT "pool_schema_pool_equipment_maintenance_technician_id_foreign" FOREIGN KEY ("technician_id") REFERENCES "pool_schema"."staff"("staff_id");


--
-- TOC entry 5351 (class 2606 OID 43323)
-- Name: pool_incidents pool_schema_pool_incidents_assigned_to_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_incidents"
    ADD CONSTRAINT "pool_schema_pool_incidents_assigned_to_foreign" FOREIGN KEY ("assigned_to") REFERENCES "pool_schema"."staff"("staff_id");


--
-- TOC entry 5352 (class 2606 OID 43318)
-- Name: pool_incidents pool_schema_pool_incidents_created_by_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_incidents"
    ADD CONSTRAINT "pool_schema_pool_incidents_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "pool_schema"."staff"("staff_id");


--
-- TOC entry 5353 (class 2606 OID 43308)
-- Name: pool_incidents pool_schema_pool_incidents_equipment_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_incidents"
    ADD CONSTRAINT "pool_schema_pool_incidents_equipment_id_foreign" FOREIGN KEY ("equipment_id") REFERENCES "pool_schema"."pool_equipment"("equipment_id");


--
-- TOC entry 5354 (class 2606 OID 43313)
-- Name: pool_incidents pool_schema_pool_incidents_pool_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_incidents"
    ADD CONSTRAINT "pool_schema_pool_incidents_pool_id_foreign" FOREIGN KEY ("pool_id") REFERENCES "pool_schema"."facilities"("facility_id");


--
-- TOC entry 5361 (class 2606 OID 43423)
-- Name: pool_monthly_tasks pool_schema_pool_monthly_tasks_facility_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_monthly_tasks"
    ADD CONSTRAINT "pool_schema_pool_monthly_tasks_facility_id_foreign" FOREIGN KEY ("facility_id") REFERENCES "pool_schema"."facilities"("facility_id") ON DELETE CASCADE;


--
-- TOC entry 5362 (class 2606 OID 43428)
-- Name: pool_monthly_tasks pool_schema_pool_monthly_tasks_technician_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_monthly_tasks"
    ADD CONSTRAINT "pool_schema_pool_monthly_tasks_technician_id_foreign" FOREIGN KEY ("technician_id") REFERENCES "pool_schema"."staff"("staff_id");


--
-- TOC entry 5363 (class 2606 OID 43459)
-- Name: pool_monthly_tasks pool_schema_pool_monthly_tasks_template_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_monthly_tasks"
    ADD CONSTRAINT "pool_schema_pool_monthly_tasks_template_id_foreign" FOREIGN KEY ("template_id") REFERENCES "pool_schema"."task_templates"("id") ON DELETE SET NULL;


--
-- TOC entry 5344 (class 2606 OID 43213)
-- Name: pool_water_tests pool_schema_pool_water_tests_pool_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_water_tests"
    ADD CONSTRAINT "pool_schema_pool_water_tests_pool_id_foreign" FOREIGN KEY ("pool_id") REFERENCES "pool_schema"."facilities"("facility_id");


--
-- TOC entry 5345 (class 2606 OID 43208)
-- Name: pool_water_tests pool_schema_pool_water_tests_technician_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_water_tests"
    ADD CONSTRAINT "pool_schema_pool_water_tests_technician_id_foreign" FOREIGN KEY ("technician_id") REFERENCES "pool_schema"."staff"("staff_id");


--
-- TOC entry 5358 (class 2606 OID 43388)
-- Name: pool_weekly_tasks pool_schema_pool_weekly_tasks_pool_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_weekly_tasks"
    ADD CONSTRAINT "pool_schema_pool_weekly_tasks_pool_id_foreign" FOREIGN KEY ("pool_id") REFERENCES "pool_schema"."facilities"("facility_id");


--
-- TOC entry 5359 (class 2606 OID 43383)
-- Name: pool_weekly_tasks pool_schema_pool_weekly_tasks_technician_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_weekly_tasks"
    ADD CONSTRAINT "pool_schema_pool_weekly_tasks_technician_id_foreign" FOREIGN KEY ("technician_id") REFERENCES "pool_schema"."staff"("staff_id");


--
-- TOC entry 5360 (class 2606 OID 43454)
-- Name: pool_weekly_tasks pool_schema_pool_weekly_tasks_template_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_weekly_tasks"
    ADD CONSTRAINT "pool_schema_pool_weekly_tasks_template_id_foreign" FOREIGN KEY ("template_id") REFERENCES "pool_schema"."task_templates"("id") ON DELETE SET NULL;


--
-- TOC entry 5335 (class 2606 OID 43005)
-- Name: products pool_schema_products_category_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."products"
    ADD CONSTRAINT "pool_schema_products_category_id_foreign" FOREIGN KEY ("category_id") REFERENCES "pool_schema"."categories"("id") ON DELETE CASCADE;


--
-- TOC entry 5309 (class 2606 OID 42712)
-- Name: role_permissions pool_schema_role_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."role_permissions"
    ADD CONSTRAINT "pool_schema_role_permissions_permission_id_foreign" FOREIGN KEY ("permission_id") REFERENCES "pool_schema"."permissions"("permission_id") ON DELETE CASCADE;


--
-- TOC entry 5310 (class 2606 OID 42707)
-- Name: role_permissions pool_schema_role_permissions_role_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."role_permissions"
    ADD CONSTRAINT "pool_schema_role_permissions_role_id_foreign" FOREIGN KEY ("role_id") REFERENCES "pool_schema"."roles"("role_id") ON DELETE CASCADE;


--
-- TOC entry 5338 (class 2606 OID 43049)
-- Name: sale_items pool_schema_sale_items_product_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."sale_items"
    ADD CONSTRAINT "pool_schema_sale_items_product_id_foreign" FOREIGN KEY ("product_id") REFERENCES "pool_schema"."products"("id") ON DELETE CASCADE;


--
-- TOC entry 5339 (class 2606 OID 43044)
-- Name: sale_items pool_schema_sale_items_sale_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."sale_items"
    ADD CONSTRAINT "pool_schema_sale_items_sale_id_foreign" FOREIGN KEY ("sale_id") REFERENCES "pool_schema"."sales"("id") ON DELETE CASCADE;


--
-- TOC entry 5336 (class 2606 OID 43026)
-- Name: sales pool_schema_sales_member_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."sales"
    ADD CONSTRAINT "pool_schema_sales_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "pool_schema"."members"("member_id") ON DELETE SET NULL;


--
-- TOC entry 5337 (class 2606 OID 43021)
-- Name: sales pool_schema_sales_staff_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."sales"
    ADD CONSTRAINT "pool_schema_sales_staff_id_foreign" FOREIGN KEY ("staff_id") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE CASCADE;


--
-- TOC entry 5341 (class 2606 OID 43091)
-- Name: staff_leaves pool_schema_staff_leaves_staff_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."staff_leaves"
    ADD CONSTRAINT "pool_schema_staff_leaves_staff_id_foreign" FOREIGN KEY ("staff_id") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE CASCADE;


--
-- TOC entry 5311 (class 2606 OID 42733)
-- Name: staff pool_schema_staff_role_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."staff"
    ADD CONSTRAINT "pool_schema_staff_role_id_foreign" FOREIGN KEY ("role_id") REFERENCES "pool_schema"."roles"("role_id") ON DELETE SET NULL;


--
-- TOC entry 5340 (class 2606 OID 43070)
-- Name: staff_schedules pool_schema_staff_schedules_staff_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."staff_schedules"
    ADD CONSTRAINT "pool_schema_staff_schedules_staff_id_foreign" FOREIGN KEY ("staff_id") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE CASCADE;


--
-- TOC entry 5331 (class 2606 OID 42944)
-- Name: subscription_allowed_days pool_schema_subscription_allowed_days_subscription_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscription_allowed_days"
    ADD CONSTRAINT "pool_schema_subscription_allowed_days_subscription_id_foreign" FOREIGN KEY ("subscription_id") REFERENCES "pool_schema"."subscriptions"("subscription_id") ON DELETE CASCADE;


--
-- TOC entry 5332 (class 2606 OID 42949)
-- Name: subscription_allowed_days pool_schema_subscription_allowed_days_weekday_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscription_allowed_days"
    ADD CONSTRAINT "pool_schema_subscription_allowed_days_weekday_id_foreign" FOREIGN KEY ("weekday_id") REFERENCES "pool_schema"."weekdays"("weekday_id") ON DELETE CASCADE;


--
-- TOC entry 5319 (class 2606 OID 42869)
-- Name: subscriptions pool_schema_subscriptions_activity_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscriptions"
    ADD CONSTRAINT "pool_schema_subscriptions_activity_id_foreign" FOREIGN KEY ("activity_id") REFERENCES "pool_schema"."activities"("activity_id") ON DELETE SET NULL;


--
-- TOC entry 5320 (class 2606 OID 42859)
-- Name: subscriptions pool_schema_subscriptions_created_by_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscriptions"
    ADD CONSTRAINT "pool_schema_subscriptions_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE SET NULL;


--
-- TOC entry 5321 (class 2606 OID 42854)
-- Name: subscriptions pool_schema_subscriptions_deactivated_by_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscriptions"
    ADD CONSTRAINT "pool_schema_subscriptions_deactivated_by_foreign" FOREIGN KEY ("deactivated_by") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE SET NULL;


--
-- TOC entry 5322 (class 2606 OID 42844)
-- Name: subscriptions pool_schema_subscriptions_member_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscriptions"
    ADD CONSTRAINT "pool_schema_subscriptions_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "pool_schema"."members"("member_id") ON DELETE CASCADE;


--
-- TOC entry 5323 (class 2606 OID 42849)
-- Name: subscriptions pool_schema_subscriptions_plan_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscriptions"
    ADD CONSTRAINT "pool_schema_subscriptions_plan_id_foreign" FOREIGN KEY ("plan_id") REFERENCES "pool_schema"."plans"("plan_id") ON DELETE CASCADE;


--
-- TOC entry 5324 (class 2606 OID 42864)
-- Name: subscriptions pool_schema_subscriptions_updated_by_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscriptions"
    ADD CONSTRAINT "pool_schema_subscriptions_updated_by_foreign" FOREIGN KEY ("updated_by") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE SET NULL;


--
-- TOC entry 5312 (class 2606 OID 42784)
-- Name: time_slots pool_schema_time_slots_activity_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."time_slots"
    ADD CONSTRAINT "pool_schema_time_slots_activity_id_foreign" FOREIGN KEY ("activity_id") REFERENCES "pool_schema"."activities"("activity_id") ON DELETE SET NULL;


--
-- TOC entry 5313 (class 2606 OID 43151)
-- Name: time_slots pool_schema_time_slots_assistant_coach_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."time_slots"
    ADD CONSTRAINT "pool_schema_time_slots_assistant_coach_id_foreign" FOREIGN KEY ("assistant_coach_id") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE SET NULL;


--
-- TOC entry 5314 (class 2606 OID 43117)
-- Name: time_slots pool_schema_time_slots_coach_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."time_slots"
    ADD CONSTRAINT "pool_schema_time_slots_coach_id_foreign" FOREIGN KEY ("coach_id") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE SET NULL;


--
-- TOC entry 5315 (class 2606 OID 42789)
-- Name: time_slots pool_schema_time_slots_created_by_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."time_slots"
    ADD CONSTRAINT "pool_schema_time_slots_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE SET NULL;


--
-- TOC entry 5316 (class 2606 OID 42779)
-- Name: time_slots pool_schema_time_slots_weekday_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."time_slots"
    ADD CONSTRAINT "pool_schema_time_slots_weekday_id_foreign" FOREIGN KEY ("weekday_id") REFERENCES "pool_schema"."weekdays"("weekday_id") ON DELETE CASCADE;


--
-- TOC entry 5343 (class 2606 OID 43142)
-- Name: product_images product_images_product_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."product_images"
    ADD CONSTRAINT "product_images_product_id_foreign" FOREIGN KEY ("product_id") REFERENCES "pool_schema"."products"("id") ON DELETE CASCADE;


--
-- TOC entry 5517 (class 0 OID 0)
-- Dependencies: 6
-- Name: SCHEMA "pool_schema"; Type: ACL; Schema: -; Owner: pooladmin
--

REVOKE ALL ON SCHEMA "pool_schema" FROM "pooladmin";
GRANT ALL ON SCHEMA "pool_schema" TO "pooladmin" WITH GRANT OPTION;


-- Completed on 2025-12-07 08:44:21

--
-- PostgreSQL database dump complete
--

\unrestrict jPeTn0GIwdCEcRcHWFN0SQ1JVl45CrbYlahJIjKkaJo3HKZIzRbgvl7wDHl1B8c


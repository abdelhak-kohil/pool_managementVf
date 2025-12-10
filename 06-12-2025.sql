--
-- PostgreSQL database dump
--

\restrict NC6pqeuFox8EqK5KTi6DmycAueXDgKWgk4PbHZar8HfecfOWxrdBJahYH40V5VC

-- Dumped from database version 18.0
-- Dumped by pg_dump version 18.0

-- Started on 2025-12-06 14:06:17

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
-- TOC entry 943 (class 1247 OID 25849)
-- Name: access_decision_enum; Type: TYPE; Schema: pool_schema; Owner: pooladmin
--

CREATE TYPE "pool_schema"."access_decision_enum" AS ENUM (
    'granted',
    'denied'
);


ALTER TYPE "pool_schema"."access_decision_enum" OWNER TO "pooladmin";

--
-- TOC entry 946 (class 1247 OID 25854)
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
-- TOC entry 949 (class 1247 OID 25866)
-- Name: facility_status_enum; Type: TYPE; Schema: pool_schema; Owner: pooladmin
--

CREATE TYPE "pool_schema"."facility_status_enum" AS ENUM (
    'operational',
    'under_maintenance',
    'closed'
);


ALTER TYPE "pool_schema"."facility_status_enum" OWNER TO "pooladmin";

--
-- TOC entry 952 (class 1247 OID 25874)
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
-- TOC entry 955 (class 1247 OID 25884)
-- Name: plan_type_enum; Type: TYPE; Schema: pool_schema; Owner: pooladmin
--

CREATE TYPE "pool_schema"."plan_type_enum" AS ENUM (
    'monthly_weekly',
    'per_visit'
);


ALTER TYPE "pool_schema"."plan_type_enum" OWNER TO "pooladmin";

--
-- TOC entry 958 (class 1247 OID 25890)
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
-- TOC entry 310 (class 1255 OID 25899)
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
-- TOC entry 309 (class 1255 OID 25900)
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
-- TOC entry 5578 (class 0 OID 0)
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
-- TOC entry 5579 (class 0 OID 0)
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
-- TOC entry 5580 (class 0 OID 0)
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
-- TOC entry 5581 (class 0 OID 0)
-- Dependencies: 267
-- Name: activity_plan_prices_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."activity_plan_prices_id_seq" OWNED BY "pool_schema"."activity_plan_prices"."id";


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
-- TOC entry 5582 (class 0 OID 0)
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
-- TOC entry 5583 (class 0 OID 0)
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
-- TOC entry 5584 (class 0 OID 0)
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
-- TOC entry 5585 (class 0 OID 0)
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
-- TOC entry 5586 (class 0 OID 0)
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
-- TOC entry 5587 (class 0 OID 0)
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
-- TOC entry 5588 (class 0 OID 0)
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
-- TOC entry 5589 (class 0 OID 0)
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
-- TOC entry 5590 (class 0 OID 0)
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
-- TOC entry 5591 (class 0 OID 0)
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
-- TOC entry 5592 (class 0 OID 0)
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
-- TOC entry 5593 (class 0 OID 0)
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
-- TOC entry 5594 (class 0 OID 0)
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
-- TOC entry 5595 (class 0 OID 0)
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
-- TOC entry 5596 (class 0 OID 0)
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
-- TOC entry 5597 (class 0 OID 0)
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
-- TOC entry 5598 (class 0 OID 0)
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
-- TOC entry 5599 (class 0 OID 0)
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
-- TOC entry 5600 (class 0 OID 0)
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
-- TOC entry 5601 (class 0 OID 0)
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
-- TOC entry 5602 (class 0 OID 0)
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
-- TOC entry 5603 (class 0 OID 0)
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
-- TOC entry 5604 (class 0 OID 0)
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
-- TOC entry 5605 (class 0 OID 0)
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
-- TOC entry 5606 (class 0 OID 0)
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
-- TOC entry 5607 (class 0 OID 0)
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
-- TOC entry 5608 (class 0 OID 0)
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
-- TOC entry 5609 (class 0 OID 0)
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
-- TOC entry 5610 (class 0 OID 0)
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
-- TOC entry 5611 (class 0 OID 0)
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
-- TOC entry 5612 (class 0 OID 0)
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
-- TOC entry 5613 (class 0 OID 0)
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
-- TOC entry 5614 (class 0 OID 0)
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
-- TOC entry 5615 (class 0 OID 0)
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
-- TOC entry 5616 (class 0 OID 0)
-- Dependencies: 247
-- Name: weekdays_weekday_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE "pool_schema"."weekdays_weekday_id_seq" OWNED BY "pool_schema"."weekdays"."weekday_id";


--
-- TOC entry 5122 (class 2604 OID 42901)
-- Name: access_badges badge_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."access_badges" ALTER COLUMN "badge_id" SET DEFAULT "nextval"('"pool_schema"."access_badges_badge_id_seq"'::"regclass");


--
-- TOC entry 5124 (class 2604 OID 42921)
-- Name: access_logs log_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."access_logs" ALTER COLUMN "log_id" SET DEFAULT "nextval"('"pool_schema"."access_logs_log_id_seq"'::"regclass");


--
-- TOC entry 5112 (class 2604 OID 42755)
-- Name: activities activity_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."activities" ALTER COLUMN "activity_id" SET DEFAULT "nextval"('"pool_schema"."activities_activity_id_seq"'::"regclass");


--
-- TOC entry 5126 (class 2604 OID 42958)
-- Name: activity_plan_prices id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."activity_plan_prices" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."activity_plan_prices_id_seq"'::"regclass");


--
-- TOC entry 5127 (class 2604 OID 42979)
-- Name: categories id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."categories" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."categories_id_seq"'::"regclass");


--
-- TOC entry 5140 (class 2604 OID 43126)
-- Name: coach_time_slot id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."coach_time_slot" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."coach_time_slot_id_seq"'::"regclass");


--
-- TOC entry 5138 (class 2604 OID 43100)
-- Name: expenses expense_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."expenses" ALTER COLUMN "expense_id" SET DEFAULT "nextval"('"pool_schema"."expenses_expense_id_seq"'::"regclass");


--
-- TOC entry 5143 (class 2604 OID 43170)
-- Name: facilities facility_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."facilities" ALTER COLUMN "facility_id" SET DEFAULT "nextval"('"pool_schema"."facilities_facility_id_seq"'::"regclass");


--
-- TOC entry 5102 (class 2604 OID 42660)
-- Name: failed_jobs id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."failed_jobs" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."failed_jobs_id_seq"'::"regclass");


--
-- TOC entry 5101 (class 2604 OID 42630)
-- Name: jobs id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."jobs" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."jobs_id_seq"'::"regclass");


--
-- TOC entry 5118 (class 2604 OID 42812)
-- Name: members member_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."members" ALTER COLUMN "member_id" SET DEFAULT "nextval"('"pool_schema"."members_member_id_seq"'::"regclass");


--
-- TOC entry 5099 (class 2604 OID 42564)
-- Name: migrations id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."migrations" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."migrations_id_seq"'::"regclass");


--
-- TOC entry 5121 (class 2604 OID 42878)
-- Name: payments payment_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."payments" ALTER COLUMN "payment_id" SET DEFAULT "nextval"('"pool_schema"."payments_payment_id_seq"'::"regclass");


--
-- TOC entry 5104 (class 2604 OID 42679)
-- Name: permissions permission_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."permissions" ALTER COLUMN "permission_id" SET DEFAULT "nextval"('"pool_schema"."permissions_permission_id_seq"'::"regclass");


--
-- TOC entry 5116 (class 2604 OID 42798)
-- Name: plans plan_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."plans" ALTER COLUMN "plan_id" SET DEFAULT "nextval"('"pool_schema"."plans_plan_id_seq"'::"regclass");


--
-- TOC entry 5149 (class 2604 OID 43222)
-- Name: pool_chemical_stock chemical_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_chemical_stock" ALTER COLUMN "chemical_id" SET DEFAULT "nextval"('"pool_schema"."pool_chemical_stock_chemical_id_seq"'::"regclass");


--
-- TOC entry 5153 (class 2604 OID 43241)
-- Name: pool_chemical_usage usage_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_chemical_usage" ALTER COLUMN "usage_id" SET DEFAULT "nextval"('"pool_schema"."pool_chemical_usage_usage_id_seq"'::"regclass");


--
-- TOC entry 5158 (class 2604 OID 43332)
-- Name: pool_daily_tasks task_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_daily_tasks" ALTER COLUMN "task_id" SET DEFAULT "nextval"('"pool_schema"."pool_daily_tasks_task_id_seq"'::"regclass");


--
-- TOC entry 5146 (class 2604 OID 43185)
-- Name: pool_equipment equipment_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_equipment" ALTER COLUMN "equipment_id" SET DEFAULT "nextval"('"pool_schema"."pool_equipment_equipment_id_seq"'::"regclass");


--
-- TOC entry 5154 (class 2604 OID 43270)
-- Name: pool_equipment_maintenance maintenance_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_equipment_maintenance" ALTER COLUMN "maintenance_id" SET DEFAULT "nextval"('"pool_schema"."pool_equipment_maintenance_maintenance_id_seq"'::"regclass");


--
-- TOC entry 5156 (class 2604 OID 43296)
-- Name: pool_incidents incident_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_incidents" ALTER COLUMN "incident_id" SET DEFAULT "nextval"('"pool_schema"."pool_incidents_incident_id_seq"'::"regclass");


--
-- TOC entry 5174 (class 2604 OID 43407)
-- Name: pool_monthly_tasks monthly_task_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_monthly_tasks" ALTER COLUMN "monthly_task_id" SET DEFAULT "nextval"('"pool_schema"."pool_monthly_tasks_monthly_task_id_seq"'::"regclass");


--
-- TOC entry 5148 (class 2604 OID 43199)
-- Name: pool_water_tests id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_water_tests" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."pool_water_tests_id_seq"'::"regclass");


--
-- TOC entry 5166 (class 2604 OID 43363)
-- Name: pool_weekly_tasks id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_weekly_tasks" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."pool_weekly_tasks_id_seq"'::"regclass");


--
-- TOC entry 5141 (class 2604 OID 43134)
-- Name: product_images id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."product_images" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."product_images_id_seq"'::"regclass");


--
-- TOC entry 5128 (class 2604 OID 42990)
-- Name: products id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."products" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."products_id_seq"'::"regclass");


--
-- TOC entry 5106 (class 2604 OID 42701)
-- Name: role_permissions id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."role_permissions" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."role_permissions_id_seq"'::"regclass");


--
-- TOC entry 5105 (class 2604 OID 42690)
-- Name: roles role_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."roles" ALTER COLUMN "role_id" SET DEFAULT "nextval"('"pool_schema"."roles_role_id_seq"'::"regclass");


--
-- TOC entry 5133 (class 2604 OID 43035)
-- Name: sale_items id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."sale_items" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."sale_items_id_seq"'::"regclass");


--
-- TOC entry 5132 (class 2604 OID 43014)
-- Name: sales id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."sales" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."sales_id_seq"'::"regclass");


--
-- TOC entry 5107 (class 2604 OID 42721)
-- Name: staff staff_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."staff" ALTER COLUMN "staff_id" SET DEFAULT "nextval"('"pool_schema"."staff_staff_id_seq"'::"regclass");


--
-- TOC entry 5136 (class 2604 OID 43079)
-- Name: staff_leaves id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."staff_leaves" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."staff_leaves_id_seq"'::"regclass");


--
-- TOC entry 5134 (class 2604 OID 43058)
-- Name: staff_schedules id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."staff_schedules" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."staff_schedules_id_seq"'::"regclass");


--
-- TOC entry 5125 (class 2604 OID 42938)
-- Name: subscription_allowed_days id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscription_allowed_days" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."subscription_allowed_days_id_seq"'::"regclass");


--
-- TOC entry 5119 (class 2604 OID 42834)
-- Name: subscriptions subscription_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscriptions" ALTER COLUMN "subscription_id" SET DEFAULT "nextval"('"pool_schema"."subscriptions_subscription_id_seq"'::"regclass");


--
-- TOC entry 5179 (class 2604 OID 43437)
-- Name: task_templates id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."task_templates" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."task_templates_id_seq"'::"regclass");


--
-- TOC entry 5114 (class 2604 OID 42768)
-- Name: time_slots slot_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."time_slots" ALTER COLUMN "slot_id" SET DEFAULT "nextval"('"pool_schema"."time_slots_slot_id_seq"'::"regclass");


--
-- TOC entry 5100 (class 2604 OID 42574)
-- Name: users id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."users" ALTER COLUMN "id" SET DEFAULT "nextval"('"pool_schema"."users_id_seq"'::"regclass");


--
-- TOC entry 5111 (class 2604 OID 42744)
-- Name: weekdays weekday_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."weekdays" ALTER COLUMN "weekday_id" SET DEFAULT "nextval"('"pool_schema"."weekdays_weekday_id_seq"'::"regclass");


--
-- TOC entry 5525 (class 0 OID 42898)
-- Dependencies: 262
-- Data for Name: access_badges; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO "pool_schema"."access_badges" VALUES (1, NULL, 'FREE-8791-OJPA', 'active', '2025-12-06 12:13:10', NULL, NULL);
INSERT INTO "pool_schema"."access_badges" VALUES (2, NULL, 'FREE-4457-FNXR', 'active', '2025-12-06 12:13:10', NULL, NULL);
INSERT INTO "pool_schema"."access_badges" VALUES (3, NULL, 'FREE-6600-HSWT', 'active', '2025-12-06 12:13:10', NULL, NULL);
INSERT INTO "pool_schema"."access_badges" VALUES (4, NULL, 'FREE-4374-SDZF', 'active', '2025-12-06 12:13:10', NULL, NULL);
INSERT INTO "pool_schema"."access_badges" VALUES (5, NULL, 'FREE-9053-ZRTZ', 'active', '2025-12-06 12:13:10', NULL, NULL);
INSERT INTO "pool_schema"."access_badges" VALUES (6, NULL, 'FREE-6372-XHRY', 'active', '2025-12-06 12:13:10', NULL, NULL);
INSERT INTO "pool_schema"."access_badges" VALUES (7, NULL, 'FREE-1284-XCOK', 'active', '2025-12-06 12:13:10', NULL, NULL);
INSERT INTO "pool_schema"."access_badges" VALUES (8, NULL, 'FREE-9394-JJEJ', 'active', '2025-12-06 12:13:10', NULL, NULL);
INSERT INTO "pool_schema"."access_badges" VALUES (9, NULL, 'FREE-0020-VADO', 'active', '2025-12-06 12:13:10', NULL, NULL);
INSERT INTO "pool_schema"."access_badges" VALUES (10, NULL, 'FREE-9819-VLSY', 'active', '2025-12-06 12:13:10', NULL, NULL);
INSERT INTO "pool_schema"."access_badges" VALUES (11, NULL, 'FREE-1273-EFRG', 'active', '2025-12-06 12:13:10', NULL, NULL);
INSERT INTO "pool_schema"."access_badges" VALUES (12, NULL, 'FREE-9190-OFFD', 'active', '2025-12-06 12:13:10', NULL, NULL);
INSERT INTO "pool_schema"."access_badges" VALUES (13, NULL, 'FREE-1387-NIZS', 'active', '2025-12-06 12:13:10', NULL, NULL);
INSERT INTO "pool_schema"."access_badges" VALUES (14, NULL, 'FREE-3643-PRZX', 'active', '2025-12-06 12:13:10', NULL, NULL);
INSERT INTO "pool_schema"."access_badges" VALUES (15, NULL, 'FREE-8628-NHZI', 'active', '2025-12-06 12:13:10', NULL, NULL);
INSERT INTO "pool_schema"."access_badges" VALUES (16, NULL, 'FREE-2732-NISU', 'active', '2025-12-06 12:13:10', NULL, NULL);
INSERT INTO "pool_schema"."access_badges" VALUES (17, NULL, 'FREE-1241-QFUS', 'active', '2025-12-06 12:13:10', NULL, NULL);
INSERT INTO "pool_schema"."access_badges" VALUES (18, NULL, 'FREE-9901-BRWF', 'active', '2025-12-06 12:13:10', NULL, NULL);
INSERT INTO "pool_schema"."access_badges" VALUES (19, NULL, 'FREE-7371-BTSF', 'active', '2025-12-06 12:13:10', NULL, NULL);
INSERT INTO "pool_schema"."access_badges" VALUES (20, NULL, 'FREE-0190-XDXM', 'active', '2025-12-06 12:13:10', NULL, NULL);


--
-- TOC entry 5527 (class 0 OID 42918)
-- Dependencies: 264
-- Data for Name: access_logs; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5513 (class 0 OID 42752)
-- Dependencies: 250
-- Data for Name: activities; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5531 (class 0 OID 42955)
-- Dependencies: 268
-- Data for Name: activity_plan_prices; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5495 (class 0 OID 42606)
-- Dependencies: 232
-- Data for Name: cache; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5496 (class 0 OID 42616)
-- Dependencies: 233
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5533 (class 0 OID 42976)
-- Dependencies: 270
-- Data for Name: categories; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO "pool_schema"."categories" VALUES (1, 'Maillots de bain', 'product', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."categories" VALUES (2, 'Équipements', 'product', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."categories" VALUES (3, 'Boissons', 'product', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."categories" VALUES (4, 'Snacks', 'product', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."categories" VALUES (5, 'Accessoires', 'product', '2025-12-06 12:13:10', '2025-12-06 12:13:10');


--
-- TOC entry 5547 (class 0 OID 43123)
-- Dependencies: 284
-- Data for Name: coach_time_slot; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5545 (class 0 OID 43097)
-- Dependencies: 282
-- Data for Name: expenses; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5551 (class 0 OID 43167)
-- Dependencies: 288
-- Data for Name: facilities; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO "pool_schema"."facilities" VALUES (1, 'Grand Bassin Olympique', 200, 'operational', 'main_pool', 2500000, 26.0, 28.0, true, NULL, NULL);
INSERT INTO "pool_schema"."facilities" VALUES (2, 'Petit Bassin d''Apprentissage', 50, 'operational', 'learning_pool', 500000, 28.0, 30.0, true, NULL, NULL);
INSERT INTO "pool_schema"."facilities" VALUES (3, 'Pataugeoire Ludique', 30, 'operational', 'kids_pool', 50000, 30.0, 32.0, true, NULL, NULL);
INSERT INTO "pool_schema"."facilities" VALUES (4, 'Jacuzzi Intérieur', 8, 'operational', 'jacuzzi', 2000, 36.0, 38.0, true, NULL, NULL);
INSERT INTO "pool_schema"."facilities" VALUES (5, 'Jacuzzi Extérieur', 10, 'under_maintenance', 'jacuzzi', 2500, 35.0, 37.0, false, NULL, NULL);
INSERT INTO "pool_schema"."facilities" VALUES (6, 'Sauna Finlandais', 12, 'operational', 'sauna', 0, 80.0, 90.0, true, NULL, NULL);
INSERT INTO "pool_schema"."facilities" VALUES (7, 'Hammam Oriental', 15, 'operational', 'hammam', 0, 40.0, 50.0, true, NULL, NULL);
INSERT INTO "pool_schema"."facilities" VALUES (8, 'Toboggan "Le Grand Bleu"', 1, 'operational', 'slide_pool', 10000, 26.0, 28.0, true, NULL, NULL);
INSERT INTO "pool_schema"."facilities" VALUES (9, 'Pataugeoire', 20, 'operational', 'kids_pool', 50000, 29.0, 31.0, true, NULL, NULL);


--
-- TOC entry 5501 (class 0 OID 42657)
-- Dependencies: 238
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5499 (class 0 OID 42642)
-- Dependencies: 236
-- Data for Name: job_batches; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5498 (class 0 OID 42627)
-- Dependencies: 235
-- Data for Name: jobs; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5519 (class 0 OID 42809)
-- Dependencies: 256
-- Data for Name: members; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5490 (class 0 OID 42561)
-- Dependencies: 227
-- Data for Name: migrations; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO "pool_schema"."migrations" VALUES (1, '0001_01_01_000000_create_users_table', 1);
INSERT INTO "pool_schema"."migrations" VALUES (2, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO "pool_schema"."migrations" VALUES (3, '0001_01_01_000002_create_jobs_table', 1);
INSERT INTO "pool_schema"."migrations" VALUES (4, '2025_11_28_000000_create_pool_schema_and_staff_tables', 1);
INSERT INTO "pool_schema"."migrations" VALUES (5, '2025_11_28_100636_add_capacity_to_time_slots_table', 1);
INSERT INTO "pool_schema"."migrations" VALUES (6, '2025_11_28_153516_add_context_columns_to_access_logs', 1);
INSERT INTO "pool_schema"."migrations" VALUES (7, '2025_11_28_180000_add_profile_fields_to_members_table', 1);
INSERT INTO "pool_schema"."migrations" VALUES (8, '2025_11_28_200051_create_staff_planning_tables', 1);
INSERT INTO "pool_schema"."migrations" VALUES (9, '2025_11_29_012114_create_expenses_table', 1);
INSERT INTO "pool_schema"."migrations" VALUES (10, '2025_11_29_021003_add_coach_id_to_time_slots', 1);
INSERT INTO "pool_schema"."migrations" VALUES (11, '2025_11_29_022329_insert_coach_role', 1);
INSERT INTO "pool_schema"."migrations" VALUES (12, '2025_11_29_023227_seed_permissions_and_roles', 1);
INSERT INTO "pool_schema"."migrations" VALUES (13, '2025_11_29_025615_create_coach_time_slot_table', 1);
INSERT INTO "pool_schema"."migrations" VALUES (14, '2025_11_29_045442_add_shop_enhancements', 1);
INSERT INTO "pool_schema"."migrations" VALUES (15, '2025_12_05_124950_add_coach_fields_to_staff_and_timeslots', 1);
INSERT INTO "pool_schema"."migrations" VALUES (16, '2025_12_05_131835_add_staff_id_to_access_badges_and_logs', 1);
INSERT INTO "pool_schema"."migrations" VALUES (17, '2025_12_06_000000_create_pool_maintenance_tables', 1);
INSERT INTO "pool_schema"."migrations" VALUES (18, '2025_12_06_002500_enhance_pool_checklists', 1);
INSERT INTO "pool_schema"."migrations" VALUES (19, '2025_12_06_004500_add_role_id_to_users_table', 1);
INSERT INTO "pool_schema"."migrations" VALUES (20, '2025_12_06_104430_create_task_templates_table', 1);


--
-- TOC entry 5493 (class 0 OID 42585)
-- Dependencies: 230
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5523 (class 0 OID 42875)
-- Dependencies: 260
-- Data for Name: payments; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5503 (class 0 OID 42676)
-- Dependencies: 240
-- Data for Name: permissions; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO "pool_schema"."permissions" VALUES (1, 'members.view');
INSERT INTO "pool_schema"."permissions" VALUES (2, 'members.create');
INSERT INTO "pool_schema"."permissions" VALUES (3, 'members.edit');
INSERT INTO "pool_schema"."permissions" VALUES (4, 'members.delete');
INSERT INTO "pool_schema"."permissions" VALUES (5, 'payments.view');
INSERT INTO "pool_schema"."permissions" VALUES (6, 'payments.create');
INSERT INTO "pool_schema"."permissions" VALUES (7, 'payments.edit');
INSERT INTO "pool_schema"."permissions" VALUES (8, 'payments.delete');
INSERT INTO "pool_schema"."permissions" VALUES (9, 'subscriptions.view');
INSERT INTO "pool_schema"."permissions" VALUES (10, 'subscriptions.create');
INSERT INTO "pool_schema"."permissions" VALUES (11, 'subscriptions.edit');
INSERT INTO "pool_schema"."permissions" VALUES (12, 'subscriptions.delete');
INSERT INTO "pool_schema"."permissions" VALUES (13, 'expenses.view');
INSERT INTO "pool_schema"."permissions" VALUES (14, 'expenses.create');
INSERT INTO "pool_schema"."permissions" VALUES (15, 'expenses.edit');
INSERT INTO "pool_schema"."permissions" VALUES (16, 'expenses.delete');
INSERT INTO "pool_schema"."permissions" VALUES (17, 'staff.view');
INSERT INTO "pool_schema"."permissions" VALUES (18, 'staff.create');
INSERT INTO "pool_schema"."permissions" VALUES (19, 'staff.edit');
INSERT INTO "pool_schema"."permissions" VALUES (20, 'staff.delete');
INSERT INTO "pool_schema"."permissions" VALUES (21, 'activities.view');
INSERT INTO "pool_schema"."permissions" VALUES (22, 'activities.create');
INSERT INTO "pool_schema"."permissions" VALUES (23, 'activities.edit');
INSERT INTO "pool_schema"."permissions" VALUES (24, 'activities.delete');
INSERT INTO "pool_schema"."permissions" VALUES (25, 'schedule.view');
INSERT INTO "pool_schema"."permissions" VALUES (26, 'schedule.create');
INSERT INTO "pool_schema"."permissions" VALUES (27, 'schedule.edit');
INSERT INTO "pool_schema"."permissions" VALUES (28, 'schedule.delete');
INSERT INTO "pool_schema"."permissions" VALUES (29, 'coaches.view');
INSERT INTO "pool_schema"."permissions" VALUES (30, 'finance.view_stats');
INSERT INTO "pool_schema"."permissions" VALUES (31, 'pool.view');
INSERT INTO "pool_schema"."permissions" VALUES (32, 'pool.manage_water');
INSERT INTO "pool_schema"."permissions" VALUES (33, 'pool.manage_equipment');
INSERT INTO "pool_schema"."permissions" VALUES (34, 'pool.manage_maintenance');
INSERT INTO "pool_schema"."permissions" VALUES (35, 'pool.manage_chemicals');
INSERT INTO "pool_schema"."permissions" VALUES (36, 'pool.manage_incidents');
INSERT INTO "pool_schema"."permissions" VALUES (37, 'pool.perform_tasks');


--
-- TOC entry 5517 (class 0 OID 42795)
-- Dependencies: 254
-- Data for Name: plans; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO "pool_schema"."plans" VALUES (1, 'per visit', NULL, 800.00, 'per_visit', NULL, NULL, true);
INSERT INTO "pool_schema"."plans" VALUES (2, 'Natation 1 fois par semain', NULL, 3500.00, 'monthly_weekly', 1, 1, true);
INSERT INTO "pool_schema"."plans" VALUES (3, 'Natation 2 fois par semain', NULL, 4500.00, 'monthly_weekly', 2, 1, true);


--
-- TOC entry 5557 (class 0 OID 43219)
-- Dependencies: 294
-- Data for Name: pool_chemical_stock; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO "pool_schema"."pool_chemical_stock" VALUES (1, 'Liquid Chlorine', 'chlorine', 150.00, 'L', 50.00, '2025-12-06 12:13:10', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_chemical_stock" VALUES (2, 'pH Minus', 'ph_minus', 75.00, 'kg', 20.00, '2025-12-06 12:13:10', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_chemical_stock" VALUES (3, 'pH Plus', 'ph_plus', 25.00, 'kg', 10.00, '2025-12-06 12:13:10', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_chemical_stock" VALUES (4, 'Flocculant Tablets', 'flocculant', 10.00, 'kg', 5.00, '2025-12-06 12:13:10', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_chemical_stock" VALUES (5, 'Anti-Algae', 'anti_algae', 40.00, 'L', 10.00, '2025-12-06 12:13:10', '2025-12-06 12:13:10', '2025-12-06 12:13:10');


--
-- TOC entry 5559 (class 0 OID 43238)
-- Dependencies: 296
-- Data for Name: pool_chemical_usage; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5565 (class 0 OID 43329)
-- Dependencies: 302
-- Data for Name: pool_daily_tasks; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO "pool_schema"."pool_daily_tasks" VALUES (1, 1, 1, '2025-12-06', 'ok', 1.20, true, false, true, true, 'RAS', '2025-12-06 12:13:10', '2025-12-06 12:13:10', true, true, true, '{"technician_id":1,"pump_status":"ok","pressure_reading":1.2,"skimmer_cleaned":true,"vacuum_done":false,"drains_checked":true,"lighting_checked":true,"debris_removed":true,"drain_covers_inspected":true,"clarity_test_passed":true,"anomalies_comment":"RAS","template_id":1}', 1);
INSERT INTO "pool_schema"."pool_daily_tasks" VALUES (2, 1, 1, '2025-12-05', 'ok', 1.10, true, true, true, true, 'Nettoyage approfondi effectué.', '2025-12-06 12:13:10', '2025-12-06 12:13:10', true, true, true, '{"technician_id":1,"pump_status":"ok","pressure_reading":1.1,"skimmer_cleaned":true,"vacuum_done":true,"drains_checked":true,"lighting_checked":true,"debris_removed":true,"drain_covers_inspected":true,"clarity_test_passed":true,"anomalies_comment":"Nettoyage approfondi effectu\u00e9.","template_id":1}', 1);
INSERT INTO "pool_schema"."pool_daily_tasks" VALUES (3, 1, 2, '2025-12-06', 'ok', 1.20, true, false, true, true, 'RAS', '2025-12-06 12:13:10', '2025-12-06 12:13:10', true, true, true, '{"technician_id":1,"pump_status":"ok","pressure_reading":1.2,"skimmer_cleaned":true,"vacuum_done":false,"drains_checked":true,"lighting_checked":true,"debris_removed":true,"drain_covers_inspected":true,"clarity_test_passed":true,"anomalies_comment":"RAS","template_id":1}', 1);
INSERT INTO "pool_schema"."pool_daily_tasks" VALUES (4, 1, 2, '2025-12-05', 'ok', 1.10, true, true, true, true, 'Nettoyage approfondi effectué.', '2025-12-06 12:13:10', '2025-12-06 12:13:10', true, true, true, '{"technician_id":1,"pump_status":"ok","pressure_reading":1.1,"skimmer_cleaned":true,"vacuum_done":true,"drains_checked":true,"lighting_checked":true,"debris_removed":true,"drain_covers_inspected":true,"clarity_test_passed":true,"anomalies_comment":"Nettoyage approfondi effectu\u00e9.","template_id":1}', 1);
INSERT INTO "pool_schema"."pool_daily_tasks" VALUES (5, 1, 3, '2025-12-06', 'ok', 1.20, true, false, true, true, 'RAS', '2025-12-06 12:13:10', '2025-12-06 12:13:10', true, true, true, '{"technician_id":1,"pump_status":"ok","pressure_reading":1.2,"skimmer_cleaned":true,"vacuum_done":false,"drains_checked":true,"lighting_checked":true,"debris_removed":true,"drain_covers_inspected":true,"clarity_test_passed":true,"anomalies_comment":"RAS","template_id":1}', 1);
INSERT INTO "pool_schema"."pool_daily_tasks" VALUES (6, 1, 3, '2025-12-05', 'ok', 1.10, true, true, true, true, 'Nettoyage approfondi effectué.', '2025-12-06 12:13:10', '2025-12-06 12:13:10', true, true, true, '{"technician_id":1,"pump_status":"ok","pressure_reading":1.1,"skimmer_cleaned":true,"vacuum_done":true,"drains_checked":true,"lighting_checked":true,"debris_removed":true,"drain_covers_inspected":true,"clarity_test_passed":true,"anomalies_comment":"Nettoyage approfondi effectu\u00e9.","template_id":1}', 1);
INSERT INTO "pool_schema"."pool_daily_tasks" VALUES (7, 1, 4, '2025-12-06', 'ok', 1.20, true, false, true, true, 'RAS', '2025-12-06 12:13:10', '2025-12-06 12:13:10', true, true, true, '{"technician_id":1,"pump_status":"ok","pressure_reading":1.2,"skimmer_cleaned":true,"vacuum_done":false,"drains_checked":true,"lighting_checked":true,"debris_removed":true,"drain_covers_inspected":true,"clarity_test_passed":true,"anomalies_comment":"RAS","template_id":1}', 1);
INSERT INTO "pool_schema"."pool_daily_tasks" VALUES (8, 1, 4, '2025-12-05', 'ok', 1.10, true, true, true, true, 'Nettoyage approfondi effectué.', '2025-12-06 12:13:10', '2025-12-06 12:13:10', true, true, true, '{"technician_id":1,"pump_status":"ok","pressure_reading":1.1,"skimmer_cleaned":true,"vacuum_done":true,"drains_checked":true,"lighting_checked":true,"debris_removed":true,"drain_covers_inspected":true,"clarity_test_passed":true,"anomalies_comment":"Nettoyage approfondi effectu\u00e9.","template_id":1}', 1);
INSERT INTO "pool_schema"."pool_daily_tasks" VALUES (9, 1, 6, '2025-12-06', 'ok', 1.20, true, false, true, true, 'RAS', '2025-12-06 12:13:10', '2025-12-06 12:13:10', true, true, true, '{"technician_id":1,"pump_status":"ok","pressure_reading":1.2,"skimmer_cleaned":true,"vacuum_done":false,"drains_checked":true,"lighting_checked":true,"debris_removed":true,"drain_covers_inspected":true,"clarity_test_passed":true,"anomalies_comment":"RAS","template_id":1}', 1);
INSERT INTO "pool_schema"."pool_daily_tasks" VALUES (10, 1, 6, '2025-12-05', 'ok', 1.10, true, true, true, true, 'Nettoyage approfondi effectué.', '2025-12-06 12:13:10', '2025-12-06 12:13:10', true, true, true, '{"technician_id":1,"pump_status":"ok","pressure_reading":1.1,"skimmer_cleaned":true,"vacuum_done":true,"drains_checked":true,"lighting_checked":true,"debris_removed":true,"drain_covers_inspected":true,"clarity_test_passed":true,"anomalies_comment":"Nettoyage approfondi effectu\u00e9.","template_id":1}', 1);
INSERT INTO "pool_schema"."pool_daily_tasks" VALUES (11, 1, 7, '2025-12-06', 'ok', 1.20, true, false, true, true, 'RAS', '2025-12-06 12:13:10', '2025-12-06 12:13:10', true, true, true, '{"technician_id":1,"pump_status":"ok","pressure_reading":1.2,"skimmer_cleaned":true,"vacuum_done":false,"drains_checked":true,"lighting_checked":true,"debris_removed":true,"drain_covers_inspected":true,"clarity_test_passed":true,"anomalies_comment":"RAS","template_id":1}', 1);
INSERT INTO "pool_schema"."pool_daily_tasks" VALUES (12, 1, 7, '2025-12-05', 'ok', 1.10, true, true, true, true, 'Nettoyage approfondi effectué.', '2025-12-06 12:13:10', '2025-12-06 12:13:10', true, true, true, '{"technician_id":1,"pump_status":"ok","pressure_reading":1.1,"skimmer_cleaned":true,"vacuum_done":true,"drains_checked":true,"lighting_checked":true,"debris_removed":true,"drain_covers_inspected":true,"clarity_test_passed":true,"anomalies_comment":"Nettoyage approfondi effectu\u00e9.","template_id":1}', 1);
INSERT INTO "pool_schema"."pool_daily_tasks" VALUES (13, 1, 8, '2025-12-06', 'ok', 1.20, true, false, true, true, 'RAS', '2025-12-06 12:13:10', '2025-12-06 12:13:10', true, true, true, '{"technician_id":1,"pump_status":"ok","pressure_reading":1.2,"skimmer_cleaned":true,"vacuum_done":false,"drains_checked":true,"lighting_checked":true,"debris_removed":true,"drain_covers_inspected":true,"clarity_test_passed":true,"anomalies_comment":"RAS","template_id":1}', 1);
INSERT INTO "pool_schema"."pool_daily_tasks" VALUES (14, 1, 8, '2025-12-05', 'ok', 1.10, true, true, true, true, 'Nettoyage approfondi effectué.', '2025-12-06 12:13:10', '2025-12-06 12:13:10', true, true, true, '{"technician_id":1,"pump_status":"ok","pressure_reading":1.1,"skimmer_cleaned":true,"vacuum_done":true,"drains_checked":true,"lighting_checked":true,"debris_removed":true,"drain_covers_inspected":true,"clarity_test_passed":true,"anomalies_comment":"Nettoyage approfondi effectu\u00e9.","template_id":1}', 1);
INSERT INTO "pool_schema"."pool_daily_tasks" VALUES (15, 1, 9, '2025-12-06', 'ok', 1.20, true, false, true, true, 'RAS', '2025-12-06 12:13:10', '2025-12-06 12:13:10', true, true, true, '{"technician_id":1,"pump_status":"ok","pressure_reading":1.2,"skimmer_cleaned":true,"vacuum_done":false,"drains_checked":true,"lighting_checked":true,"debris_removed":true,"drain_covers_inspected":true,"clarity_test_passed":true,"anomalies_comment":"RAS","template_id":1}', 1);
INSERT INTO "pool_schema"."pool_daily_tasks" VALUES (16, 1, 9, '2025-12-05', 'ok', 1.10, true, true, true, true, 'Nettoyage approfondi effectué.', '2025-12-06 12:13:10', '2025-12-06 12:13:10', true, true, true, '{"technician_id":1,"pump_status":"ok","pressure_reading":1.1,"skimmer_cleaned":true,"vacuum_done":true,"drains_checked":true,"lighting_checked":true,"debris_removed":true,"drain_covers_inspected":true,"clarity_test_passed":true,"anomalies_comment":"Nettoyage approfondi effectu\u00e9.","template_id":1}', 1);


--
-- TOC entry 5553 (class 0 OID 43182)
-- Dependencies: 290
-- Data for Name: pool_equipment; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO "pool_schema"."pool_equipment" VALUES (1, 'Main Pump A', 'pump', 'PUMP-2024-001', 'Technical Room 1', '2024-01-15', 'operational', NULL, '2026-03-06', NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_equipment" VALUES (2, 'Sand Filter 1', 'filter', 'FILT-2024-001', 'Technical Room 1', '2024-01-15', 'operational', NULL, '2026-01-06', NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_equipment" VALUES (3, 'Chlorine Dosing Pump', 'dosing_machine', 'DOSE-2024-001', 'Technical Room 1', '2024-02-01', 'warning', NULL, '2025-12-11', 'Flow rate slightly unstable', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_equipment" VALUES (4, 'Heat Pump', 'heater', 'HEAT-2024-001', 'External Unit', '2023-11-20', 'operational', NULL, '2026-06-06', NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');


--
-- TOC entry 5561 (class 0 OID 43267)
-- Dependencies: 298
-- Data for Name: pool_equipment_maintenance; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5563 (class 0 OID 43293)
-- Dependencies: 300
-- Data for Name: pool_incidents; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO "pool_schema"."pool_incidents" VALUES (1, 'Glass broken near pool edge', 'Glass broken near pool edge.', 'high', NULL, 1, 1, NULL, 'resolved', '2025-11-26 12:13:15', '2025-11-26 14:13:15');
INSERT INTO "pool_schema"."pool_incidents" VALUES (2, 'Ladder loose', 'Ladder loose.', 'medium', NULL, 1, 1, NULL, 'open', '2025-12-05 12:13:15', '2025-12-06 12:13:15');


--
-- TOC entry 5569 (class 0 OID 43404)
-- Dependencies: 306
-- Data for Name: pool_monthly_tasks; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO "pool_schema"."pool_monthly_tasks" VALUES (1, 1, 1, true, true, false, 'Calibration à prévoir semaine prochaine.', '2025-12-01 00:00:00', '{"technician_id":1,"water_replacement_partial":true,"full_system_inspection":true,"chemical_dosing_calibration":false,"notes":"Calibration \u00e0 pr\u00e9voir semaine prochaine.","template_id":3}', 3);
INSERT INTO "pool_schema"."pool_monthly_tasks" VALUES (2, 2, 1, true, true, false, 'Calibration à prévoir semaine prochaine.', '2025-12-01 00:00:00', '{"technician_id":1,"water_replacement_partial":true,"full_system_inspection":true,"chemical_dosing_calibration":false,"notes":"Calibration \u00e0 pr\u00e9voir semaine prochaine.","template_id":3}', 3);
INSERT INTO "pool_schema"."pool_monthly_tasks" VALUES (3, 3, 1, true, true, false, 'Calibration à prévoir semaine prochaine.', '2025-12-01 00:00:00', '{"technician_id":1,"water_replacement_partial":true,"full_system_inspection":true,"chemical_dosing_calibration":false,"notes":"Calibration \u00e0 pr\u00e9voir semaine prochaine.","template_id":3}', 3);
INSERT INTO "pool_schema"."pool_monthly_tasks" VALUES (4, 4, 1, true, true, false, 'Calibration à prévoir semaine prochaine.', '2025-12-01 00:00:00', '{"technician_id":1,"water_replacement_partial":true,"full_system_inspection":true,"chemical_dosing_calibration":false,"notes":"Calibration \u00e0 pr\u00e9voir semaine prochaine.","template_id":3}', 3);
INSERT INTO "pool_schema"."pool_monthly_tasks" VALUES (5, 6, 1, true, true, false, 'Calibration à prévoir semaine prochaine.', '2025-12-01 00:00:00', '{"technician_id":1,"water_replacement_partial":true,"full_system_inspection":true,"chemical_dosing_calibration":false,"notes":"Calibration \u00e0 pr\u00e9voir semaine prochaine.","template_id":3}', 3);
INSERT INTO "pool_schema"."pool_monthly_tasks" VALUES (6, 7, 1, true, true, false, 'Calibration à prévoir semaine prochaine.', '2025-12-01 00:00:00', '{"technician_id":1,"water_replacement_partial":true,"full_system_inspection":true,"chemical_dosing_calibration":false,"notes":"Calibration \u00e0 pr\u00e9voir semaine prochaine.","template_id":3}', 3);
INSERT INTO "pool_schema"."pool_monthly_tasks" VALUES (7, 8, 1, true, true, false, 'Calibration à prévoir semaine prochaine.', '2025-12-01 00:00:00', '{"technician_id":1,"water_replacement_partial":true,"full_system_inspection":true,"chemical_dosing_calibration":false,"notes":"Calibration \u00e0 pr\u00e9voir semaine prochaine.","template_id":3}', 3);
INSERT INTO "pool_schema"."pool_monthly_tasks" VALUES (8, 9, 1, true, true, false, 'Calibration à prévoir semaine prochaine.', '2025-12-01 00:00:00', '{"technician_id":1,"water_replacement_partial":true,"full_system_inspection":true,"chemical_dosing_calibration":false,"notes":"Calibration \u00e0 pr\u00e9voir semaine prochaine.","template_id":3}', 3);


--
-- TOC entry 5555 (class 0 OID 43196)
-- Dependencies: 292
-- Data for Name: pool_water_tests; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO "pool_schema"."pool_water_tests" VALUES (1, '2025-12-06 08:49:10', 1, 1, 7.50, 0.90, 1.60, NULL, 107, 272, 3130, 0.60, 27.8, 766, 'Routine monthly check', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2, '2025-12-05 08:48:10', 1, 1, 7.10, 1.40, 3.00, NULL, 86, 275, 3849, 0.60, 26.2, 750, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (3, '2025-12-04 10:53:10', 1, 1, 7.40, 0.90, 3.10, NULL, 81, 248, 3563, 0.50, 26.3, 721, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (4, '2025-12-03 09:21:10', 1, 1, 7.30, 0.50, 3.20, NULL, 99, 263, 3559, 0.20, 27.2, 694, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (5, '2025-12-02 09:40:10', 1, 1, 7.40, 2.20, 3.20, NULL, 100, 215, 3571, 0.50, 28.2, 770, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (6, '2025-12-01 10:49:10', 1, 1, 7.40, 1.80, 1.80, NULL, 113, 245, 3724, 0.70, 28.4, 707, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (7, '2025-11-30 10:28:10', 1, 1, 7.80, 1.40, 1.10, NULL, 111, 400, 3216, 0.90, 27.5, 652, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (8, '2025-11-29 09:51:10', 1, 1, 7.60, 1.80, 1.80, NULL, 120, 257, 3142, 0.20, 28.4, 655, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (9, '2025-11-28 08:15:10', 1, 1, 7.30, 2.70, 1.30, NULL, 97, 317, 3209, 0.10, 26.8, 763, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (10, '2025-11-27 08:18:10', 1, 1, 7.80, 0.70, 2.30, NULL, 118, 399, 3252, 0.40, 28.4, 697, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (11, '2025-11-26 09:25:10', 1, 1, 7.30, 1.20, 1.70, NULL, 116, 291, 3409, 0.20, 28.2, 752, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (12, '2025-11-25 10:05:10', 1, 1, 7.30, 1.60, 1.70, NULL, 95, 344, 3587, 0.90, 26.5, 743, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (13, '2025-11-24 09:24:10', 1, 1, 7.00, 2.30, 2.70, NULL, 114, 292, 3671, 0.80, 28.1, 680, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (14, '2025-11-23 10:06:10', 1, 1, 7.30, 1.80, 0.60, NULL, 82, 219, 3928, 0.80, 26.4, 734, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (15, '2025-11-22 09:27:10', 1, 1, 7.30, 0.80, 2.90, NULL, 83, 294, 3490, 1.00, 28.1, 775, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (16, '2025-11-21 10:53:10', 1, 1, 7.80, 2.40, 1.70, NULL, 82, 299, 3576, 0.30, 28.7, 669, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (17, '2025-11-20 09:24:10', 1, 1, 7.20, 1.40, 2.10, NULL, 97, 310, 3014, 1.00, 27.1, 779, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (18, '2025-11-19 08:07:10', 1, 1, 7.30, 1.20, 3.40, NULL, 94, 386, 3184, 0.00, 27.2, 734, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (19, '2025-11-18 11:15:10', 1, 1, 7.50, 2.10, 3.50, NULL, 103, 233, 3159, 0.20, 27.1, 738, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (20, '2025-11-17 08:53:10', 1, 1, 7.80, 2.30, 0.80, NULL, 119, 391, 3983, 1.00, 29.5, 677, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (21, '2025-11-16 08:51:10', 1, 1, 7.50, 1.30, 0.80, NULL, 104, 298, 3035, 0.50, 28.8, 715, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (22, '2025-11-15 08:27:10', 1, 1, 7.00, 1.20, 3.30, NULL, 102, 287, 3428, 0.90, 28.1, 680, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (23, '2025-11-14 09:29:10', 1, 1, 7.70, 2.50, 0.50, NULL, 90, 273, 3010, 0.60, 28.0, 771, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (24, '2025-11-13 10:37:10', 1, 1, 7.50, 2.00, 0.60, NULL, 83, 265, 3995, 0.60, 27.8, 696, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (25, '2025-11-12 11:26:10', 1, 1, 7.00, 2.70, 3.30, NULL, 102, 365, 3591, 0.80, 26.1, 728, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (26, '2025-11-11 11:21:10', 1, 1, 7.00, 0.50, 2.20, NULL, 86, 310, 3226, 0.60, 29.9, 767, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (27, '2025-11-10 10:21:10', 1, 1, 7.10, 2.50, 1.40, NULL, 82, 252, 3163, 0.70, 27.3, 724, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (28, '2025-11-09 10:49:10', 1, 1, 7.80, 1.60, 3.40, NULL, 85, 253, 3735, 1.00, 28.7, 767, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (29, '2025-11-08 10:31:10', 1, 1, 7.80, 2.20, 2.80, NULL, 85, 344, 3405, 0.70, 28.6, 709, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (30, '2025-11-07 08:46:10', 1, 1, 7.60, 2.50, 1.50, NULL, 119, 336, 3853, 0.40, 27.4, 655, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (31, '2025-11-06 10:31:10', 1, 1, 7.00, 1.70, 2.90, NULL, 86, 284, 3183, 0.40, 27.8, 717, 'Routine monthly check', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (32, '2025-11-05 09:15:10', 1, 1, 7.30, 2.10, 2.20, NULL, 92, 333, 3779, 0.90, 29.8, 695, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (33, '2025-11-04 09:36:10', 1, 1, 7.00, 3.00, 3.40, NULL, 115, 295, 3331, 0.40, 27.9, 762, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (34, '2025-11-03 11:27:10', 1, 1, 7.50, 2.70, 3.20, NULL, 82, 313, 3194, 0.00, 27.8, 795, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (35, '2025-11-02 09:20:10', 1, 1, 7.10, 1.90, 3.20, NULL, 99, 347, 3421, 0.70, 28.5, 709, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (36, '2025-11-01 09:49:10', 1, 1, 7.50, 2.50, 3.30, NULL, 81, 387, 3891, 0.60, 28.6, 668, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (37, '2025-10-31 08:55:10', 1, 1, 7.60, 2.30, 0.70, NULL, 96, 387, 3854, 0.00, 29.9, 676, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (38, '2025-10-30 09:35:10', 1, 1, 7.80, 0.90, 3.20, NULL, 84, 218, 3050, 0.60, 27.1, 737, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (39, '2025-10-29 08:11:10', 1, 1, 7.70, 1.10, 3.00, NULL, 99, 228, 3535, 0.00, 29.3, 752, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (40, '2025-10-28 08:55:10', 1, 1, 7.70, 2.90, 0.60, NULL, 94, 232, 3442, 0.30, 28.2, 685, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (41, '2025-10-27 10:21:10', 1, 1, 7.00, 1.10, 2.90, NULL, 106, 379, 3771, 0.50, 29.4, 730, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (42, '2025-10-26 10:57:10', 1, 1, 7.40, 2.20, 3.20, NULL, 102, 222, 3959, 0.90, 27.8, 712, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (43, '2025-10-25 09:30:10', 1, 1, 7.80, 2.60, 2.40, NULL, 89, 223, 3939, 0.40, 28.0, 744, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (44, '2025-10-24 10:11:10', 1, 1, 7.10, 1.50, 1.20, NULL, 120, 297, 3483, 0.80, 28.6, 796, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (45, '2025-10-23 10:33:10', 1, 1, 7.60, 2.80, 2.60, NULL, 80, 253, 3983, 1.00, 26.6, 712, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (46, '2025-10-22 09:19:10', 1, 1, 7.40, 1.70, 1.30, NULL, 115, 259, 3433, 0.60, 26.2, 691, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (47, '2025-10-21 08:05:10', 1, 1, 7.10, 1.00, 2.80, NULL, 101, 257, 3679, 0.90, 29.5, 789, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (48, '2025-10-20 11:59:10', 1, 1, 7.80, 1.40, 2.20, NULL, 114, 354, 3205, 0.40, 26.1, 751, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (49, '2025-10-19 09:54:10', 1, 1, 7.00, 1.00, 1.30, NULL, 82, 392, 3365, 0.30, 28.3, 699, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (50, '2025-10-18 11:31:10', 1, 1, 7.00, 3.00, 1.90, NULL, 85, 311, 3564, 0.60, 28.8, 765, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (51, '2025-10-17 08:08:10', 1, 1, 7.30, 2.80, 2.00, NULL, 100, 359, 3528, 0.40, 26.5, 723, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (52, '2025-10-16 09:42:10', 1, 1, 7.40, 2.30, 1.00, NULL, 92, 353, 3286, 0.50, 29.8, 674, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (53, '2025-10-15 08:36:10', 1, 1, 7.40, 1.50, 1.50, NULL, 106, 321, 3166, 0.70, 28.3, 787, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (54, '2025-10-14 10:26:10', 1, 1, 7.70, 1.00, 3.00, NULL, 109, 333, 3624, 0.50, 28.4, 781, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (55, '2025-10-13 11:34:10', 1, 1, 7.80, 0.50, 1.40, NULL, 119, 212, 3664, 1.00, 26.7, 763, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (56, '2025-10-12 10:14:10', 1, 1, 7.80, 1.00, 2.90, NULL, 110, 245, 3567, 1.00, 26.3, 719, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (57, '2025-10-11 08:09:10', 1, 1, 7.30, 1.00, 1.00, NULL, 111, 300, 3145, 0.90, 26.5, 793, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (58, '2025-10-10 11:52:10', 1, 1, 7.30, 1.20, 1.20, NULL, 96, 203, 3350, 0.50, 29.4, 698, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (59, '2025-10-09 08:31:10', 1, 1, 7.50, 1.90, 3.20, NULL, 90, 242, 3876, 0.10, 26.5, 793, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (60, '2025-10-08 08:34:10', 1, 1, 7.00, 2.40, 3.30, NULL, 88, 279, 3027, 0.10, 29.7, 685, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (61, '2025-10-07 08:48:10', 1, 1, 7.10, 2.00, 2.70, NULL, 98, 224, 3641, 0.60, 27.7, 737, 'Routine monthly check', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (62, '2025-10-06 11:22:10', 1, 1, 7.60, 2.10, 1.80, NULL, 112, 224, 3506, 0.30, 28.5, 695, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (63, '2025-10-05 09:44:10', 1, 1, 7.80, 2.30, 0.50, NULL, 93, 300, 3798, 0.20, 26.7, 776, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (64, '2025-10-04 10:49:10', 1, 1, 7.80, 2.90, 3.50, NULL, 82, 390, 3737, 0.60, 26.1, 661, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (65, '2025-10-03 09:55:10', 1, 1, 7.10, 1.80, 2.10, NULL, 111, 371, 3923, 1.00, 28.2, 675, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (66, '2025-10-02 11:27:10', 1, 1, 7.10, 1.60, 2.00, NULL, 105, 293, 3813, 0.50, 26.2, 738, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (67, '2025-10-01 09:30:10', 1, 1, 7.30, 1.10, 1.90, NULL, 98, 334, 3124, 0.60, 28.1, 689, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (68, '2025-09-30 09:31:10', 1, 1, 7.40, 1.60, 2.30, NULL, 101, 377, 3598, 0.70, 26.8, 692, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (69, '2025-09-29 10:15:10', 1, 1, 7.30, 2.40, 3.20, NULL, 80, 268, 3220, 0.60, 28.9, 704, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (70, '2025-09-28 08:59:10', 1, 1, 7.60, 2.90, 1.00, NULL, 93, 212, 3042, 1.00, 27.1, 741, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (71, '2025-09-27 08:57:10', 1, 1, 7.20, 1.40, 3.50, NULL, 91, 324, 3172, 0.10, 30.0, 657, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (72, '2025-09-26 11:26:10', 1, 1, 7.50, 2.00, 2.40, NULL, 87, 400, 3624, 0.80, 26.3, 736, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (73, '2025-09-25 08:39:10', 1, 1, 7.70, 2.80, 2.50, NULL, 111, 323, 3933, 0.20, 26.5, 755, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (74, '2025-09-24 09:06:10', 1, 1, 7.30, 2.10, 3.10, NULL, 93, 316, 3434, 1.00, 29.9, 718, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (75, '2025-09-23 09:23:10', 1, 1, 7.30, 2.80, 1.60, NULL, 85, 249, 3378, 1.00, 29.4, 702, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (76, '2025-09-22 11:57:10', 1, 1, 7.40, 1.30, 1.80, NULL, 113, 318, 3389, 0.80, 28.0, 654, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (77, '2025-09-21 11:21:10', 1, 1, 7.40, 2.10, 1.60, NULL, 101, 370, 3871, 0.00, 26.1, 671, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (78, '2025-09-20 08:08:10', 1, 1, 7.60, 2.60, 2.70, NULL, 106, 369, 3174, 0.40, 27.7, 784, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (79, '2025-09-19 10:42:10', 1, 1, 7.40, 1.70, 3.00, NULL, 82, 392, 3835, 0.60, 26.9, 650, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (80, '2025-09-18 11:44:10', 1, 1, 7.40, 1.20, 1.60, NULL, 92, 246, 3215, 0.90, 28.0, 719, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (81, '2025-09-17 10:30:10', 1, 1, 7.80, 1.90, 2.10, NULL, 81, 342, 3690, 0.80, 28.0, 713, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (82, '2025-09-16 10:16:10', 1, 1, 7.30, 0.60, 3.50, NULL, 97, 394, 3067, 0.10, 26.8, 784, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (83, '2025-09-15 09:49:10', 1, 1, 7.40, 2.90, 2.10, NULL, 87, 329, 3299, 1.00, 29.1, 792, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (84, '2025-09-14 08:44:10', 1, 1, 7.60, 1.50, 3.40, NULL, 95, 272, 3465, 0.90, 28.5, 674, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (85, '2025-09-13 11:27:10', 1, 1, 7.30, 2.40, 2.00, NULL, 116, 264, 3679, 0.60, 26.9, 767, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (86, '2025-09-12 10:39:10', 1, 1, 7.80, 2.70, 2.10, NULL, 93, 249, 3604, 0.00, 28.4, 793, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (87, '2025-09-11 11:59:10', 1, 1, 7.40, 1.70, 3.40, NULL, 82, 256, 3425, 0.10, 26.8, 787, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (88, '2025-09-10 10:43:10', 1, 1, 7.10, 2.70, 1.90, NULL, 85, 324, 3516, 0.80, 29.9, 662, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (89, '2025-09-09 11:37:10', 1, 1, 7.50, 2.50, 2.50, NULL, 97, 262, 3891, 0.40, 29.3, 661, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (90, '2025-09-08 09:34:10', 1, 1, 7.60, 1.30, 1.50, NULL, 87, 399, 3882, 0.00, 26.3, 650, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (91, '2025-09-07 09:51:10', 1, 1, 7.50, 3.00, 2.80, NULL, 91, 305, 3511, 0.00, 27.2, 752, 'Routine monthly check', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (92, '2025-09-06 10:10:10', 1, 1, 7.10, 2.00, 0.90, NULL, 89, 294, 3069, 1.00, 27.1, 684, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (93, '2025-09-05 08:33:10', 1, 1, 7.70, 1.60, 2.70, NULL, 82, 289, 3180, 0.40, 26.9, 675, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (94, '2025-09-04 11:32:10', 1, 1, 7.70, 1.60, 3.50, NULL, 120, 261, 3337, 0.50, 28.5, 693, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (95, '2025-09-03 09:49:10', 1, 1, 7.10, 2.00, 3.40, NULL, 115, 372, 3627, 0.00, 26.4, 733, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (96, '2025-09-02 08:50:10', 1, 1, 7.60, 2.60, 1.40, NULL, 105, 353, 3034, 0.40, 29.8, 697, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (97, '2025-09-01 11:17:10', 1, 1, 7.00, 1.80, 2.10, NULL, 108, 251, 3927, 0.90, 27.4, 799, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (98, '2025-08-31 09:15:10', 1, 1, 7.50, 3.00, 2.20, NULL, 81, 360, 3175, 0.80, 27.1, 742, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (99, '2025-08-30 10:37:10', 1, 1, 7.20, 1.70, 1.60, NULL, 111, 280, 3411, 0.90, 26.7, 791, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (100, '2025-08-29 11:44:10', 1, 1, 7.60, 0.70, 3.20, NULL, 112, 384, 3806, 0.90, 26.6, 656, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (101, '2025-08-28 08:00:10', 1, 1, 7.50, 2.40, 3.40, NULL, 103, 323, 3158, 0.80, 26.1, 670, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (102, '2025-08-27 08:48:10', 1, 1, 7.20, 1.20, 1.10, NULL, 89, 211, 3269, 0.50, 27.2, 666, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (103, '2025-08-26 08:04:10', 1, 1, 7.30, 1.90, 3.10, NULL, 85, 285, 3839, 0.70, 29.3, 758, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (104, '2025-08-25 08:01:10', 1, 1, 7.60, 2.00, 1.00, NULL, 90, 232, 3355, 0.60, 27.6, 787, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (105, '2025-08-24 11:45:10', 1, 1, 7.40, 1.90, 3.40, NULL, 90, 328, 3981, 0.90, 26.2, 723, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (106, '2025-08-23 10:00:10', 1, 1, 7.30, 1.10, 1.00, NULL, 118, 338, 3957, 0.80, 26.7, 790, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (107, '2025-08-22 11:21:10', 1, 1, 7.60, 2.60, 0.80, NULL, 90, 221, 3847, 0.80, 29.8, 766, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (108, '2025-08-21 11:31:10', 1, 1, 7.20, 1.00, 1.30, NULL, 97, 252, 3791, 1.00, 28.2, 656, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (109, '2025-08-20 08:05:10', 1, 1, 7.30, 1.10, 1.20, NULL, 93, 382, 3468, 0.30, 28.9, 799, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (110, '2025-08-19 09:35:10', 1, 1, 7.20, 2.20, 0.70, NULL, 117, 350, 3442, 1.00, 29.4, 763, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (111, '2025-08-18 11:37:10', 1, 1, 7.50, 0.80, 1.50, NULL, 120, 201, 3949, 0.00, 28.1, 797, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (112, '2025-08-17 11:57:10', 1, 1, 7.30, 0.60, 2.00, NULL, 106, 250, 3808, 0.80, 28.6, 667, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (113, '2025-08-16 11:36:10', 1, 1, 7.00, 2.90, 2.40, NULL, 116, 205, 3609, 0.30, 26.0, 750, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (114, '2025-08-15 10:02:10', 1, 1, 7.70, 2.10, 2.70, NULL, 96, 259, 3498, 0.50, 26.6, 779, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (115, '2025-08-14 09:15:10', 1, 1, 7.10, 2.30, 3.00, NULL, 93, 321, 3413, 0.70, 29.6, 749, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (116, '2025-08-13 10:26:10', 1, 1, 7.40, 1.50, 1.70, NULL, 119, 384, 3169, 0.80, 29.2, 712, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (117, '2025-08-12 09:19:10', 1, 1, 7.30, 2.00, 1.50, NULL, 100, 353, 3083, 0.60, 29.9, 776, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (118, '2025-08-11 11:40:10', 1, 1, 7.40, 0.80, 1.60, NULL, 110, 215, 3195, 0.90, 29.6, 659, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (119, '2025-08-10 11:22:10', 1, 1, 7.20, 0.70, 0.80, NULL, 89, 270, 3618, 1.00, 28.3, 728, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (120, '2025-08-09 09:14:10', 1, 1, 7.30, 2.40, 2.00, NULL, 111, 267, 3544, 0.20, 26.9, 767, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (121, '2025-08-08 08:19:10', 1, 1, 7.00, 2.00, 1.30, NULL, 88, 350, 3307, 0.90, 30.0, 768, 'Routine monthly check', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (122, '2025-08-07 11:18:10', 1, 1, 7.40, 2.20, 1.50, NULL, 116, 218, 3610, 0.20, 28.7, 759, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (123, '2025-08-06 09:08:10', 1, 1, 7.70, 2.10, 1.70, NULL, 111, 245, 3713, 0.20, 26.5, 738, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (124, '2025-08-05 08:43:10', 1, 1, 7.70, 3.00, 0.60, NULL, 119, 230, 3734, 0.40, 29.6, 656, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (125, '2025-08-04 08:34:10', 1, 1, 7.40, 1.10, 3.40, NULL, 117, 242, 3697, 0.20, 28.2, 745, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (126, '2025-08-03 08:13:10', 1, 1, 7.00, 3.00, 1.80, NULL, 91, 293, 3376, 0.20, 29.7, 745, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (127, '2025-08-02 10:09:10', 1, 1, 7.40, 2.30, 1.70, NULL, 107, 278, 3556, 1.00, 27.6, 734, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (128, '2025-08-01 11:30:10', 1, 1, 7.80, 0.50, 0.90, NULL, 118, 250, 3170, 0.50, 27.8, 787, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (129, '2025-07-31 08:24:10', 1, 1, 7.10, 1.70, 0.80, NULL, 80, 212, 3664, 0.00, 26.1, 797, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (130, '2025-07-30 10:35:10', 1, 1, 7.30, 0.60, 1.60, NULL, 109, 319, 3188, 1.00, 28.2, 691, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (131, '2025-07-29 10:56:10', 1, 1, 7.40, 2.90, 2.80, NULL, 120, 268, 3880, 0.60, 27.5, 698, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (132, '2025-07-28 09:31:10', 1, 1, 7.10, 1.90, 1.30, NULL, 103, 368, 3536, 0.30, 28.0, 755, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (133, '2025-07-27 09:30:10', 1, 1, 7.20, 2.00, 1.40, NULL, 101, 248, 3566, 0.50, 26.7, 664, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (134, '2025-07-26 09:31:10', 1, 1, 7.70, 1.50, 2.10, NULL, 95, 349, 3287, 0.40, 26.7, 663, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (135, '2025-07-25 09:32:10', 1, 1, 7.50, 1.10, 2.40, NULL, 117, 285, 3869, 0.10, 28.8, 684, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (136, '2025-07-24 11:24:10', 1, 1, 7.10, 0.60, 1.20, NULL, 110, 249, 3132, 0.80, 29.2, 731, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (137, '2025-07-23 10:14:10', 1, 1, 7.10, 1.80, 0.90, NULL, 84, 261, 3414, 0.20, 28.8, 726, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (138, '2025-07-22 10:37:10', 1, 1, 7.00, 2.40, 1.60, NULL, 104, 224, 3800, 0.10, 29.9, 755, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (139, '2025-07-21 09:51:10', 1, 1, 7.50, 1.00, 2.40, NULL, 95, 263, 3093, 1.00, 29.4, 685, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (140, '2025-07-20 10:50:10', 1, 1, 7.20, 2.50, 1.50, NULL, 113, 277, 3033, 0.40, 26.9, 715, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (141, '2025-07-19 10:22:10', 1, 1, 7.80, 3.00, 0.70, NULL, 118, 288, 3612, 0.00, 27.2, 768, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (142, '2025-07-18 10:39:10', 1, 1, 7.20, 2.80, 3.00, NULL, 94, 353, 3095, 0.90, 28.5, 763, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (143, '2025-07-17 09:44:10', 1, 1, 7.50, 2.20, 1.40, NULL, 96, 315, 3370, 0.00, 26.1, 699, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (144, '2025-07-16 11:54:10', 1, 1, 7.50, 1.20, 2.00, NULL, 104, 316, 3387, 0.20, 28.9, 781, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (145, '2025-07-15 11:39:10', 1, 1, 7.00, 2.10, 2.20, NULL, 120, 299, 3545, 0.00, 27.7, 757, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (146, '2025-07-14 10:37:10', 1, 1, 7.10, 2.10, 1.90, NULL, 90, 257, 3402, 0.60, 28.2, 737, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (147, '2025-07-13 08:26:10', 1, 1, 7.00, 2.20, 2.50, NULL, 98, 283, 3490, 0.70, 26.1, 754, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (148, '2025-07-12 11:46:10', 1, 1, 7.30, 1.20, 3.20, NULL, 104, 273, 3103, 0.60, 26.1, 668, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (149, '2025-07-11 11:30:10', 1, 1, 7.60, 2.50, 0.70, NULL, 107, 365, 3151, 0.80, 26.5, 773, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (150, '2025-07-10 08:16:10', 1, 1, 7.60, 2.30, 2.10, NULL, 85, 311, 3929, 0.30, 29.8, 762, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (151, '2025-07-09 09:02:10', 1, 1, 7.20, 0.50, 2.60, NULL, 102, 218, 3854, 0.40, 29.4, 777, 'Routine monthly check', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (152, '2025-07-08 08:53:10', 1, 1, 7.70, 0.90, 3.30, NULL, 91, 355, 3514, 0.40, 29.4, 708, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (153, '2025-07-07 10:29:10', 1, 1, 7.00, 1.20, 3.10, NULL, 99, 301, 3917, 0.10, 29.3, 660, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (154, '2025-07-06 08:08:10', 1, 1, 7.20, 1.10, 1.10, NULL, 106, 314, 3303, 0.90, 26.0, 655, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (155, '2025-07-05 08:28:10', 1, 1, 7.70, 1.40, 3.40, NULL, 100, 306, 3791, 0.80, 26.6, 786, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (156, '2025-07-04 10:43:10', 1, 1, 7.60, 1.40, 2.80, NULL, 89, 391, 3644, 0.50, 26.1, 797, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (157, '2025-07-03 09:41:10', 1, 1, 7.50, 1.80, 1.50, NULL, 119, 361, 3740, 0.90, 26.1, 775, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (158, '2025-07-02 08:03:10', 1, 1, 7.30, 1.30, 1.70, NULL, 88, 328, 3510, 0.60, 27.0, 743, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (159, '2025-07-01 10:01:10', 1, 1, 7.60, 1.00, 3.10, NULL, 100, 297, 3764, 0.30, 27.0, 724, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (160, '2025-06-30 10:42:10', 1, 1, 7.20, 0.70, 1.90, NULL, 89, 304, 3962, 0.00, 26.3, 730, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (161, '2025-06-29 09:37:10', 1, 1, 7.10, 2.50, 1.20, NULL, 99, 317, 3270, 0.70, 27.6, 668, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (162, '2025-06-28 11:52:10', 1, 1, 7.00, 2.20, 1.70, NULL, 109, 338, 3010, 0.70, 29.6, 777, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (163, '2025-06-27 08:11:10', 1, 1, 7.10, 0.90, 1.60, NULL, 104, 335, 3164, 0.10, 27.5, 675, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (164, '2025-06-26 09:40:10', 1, 1, 7.70, 1.60, 1.20, NULL, 103, 289, 3800, 1.00, 27.0, 739, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (165, '2025-06-25 09:35:10', 1, 1, 7.10, 3.00, 3.50, NULL, 90, 287, 3992, 0.90, 26.0, 732, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (166, '2025-06-24 09:17:10', 1, 1, 7.20, 3.00, 2.20, NULL, 118, 277, 3716, 0.50, 29.7, 788, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (167, '2025-06-23 10:49:10', 1, 1, 7.00, 0.90, 3.30, NULL, 85, 353, 3877, 0.70, 27.4, 762, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (168, '2025-06-22 10:39:10', 1, 1, 7.30, 0.50, 2.50, NULL, 88, 205, 3800, 0.60, 29.8, 713, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (169, '2025-06-21 08:33:10', 1, 1, 7.10, 2.00, 2.40, NULL, 82, 316, 3773, 0.60, 28.0, 742, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (170, '2025-06-20 11:21:10', 1, 1, 7.10, 2.50, 0.60, NULL, 100, 382, 3146, 0.70, 26.9, 753, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (171, '2025-06-19 11:01:10', 1, 1, 7.10, 2.30, 1.10, NULL, 115, 248, 3658, 0.70, 27.2, 725, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (172, '2025-06-18 08:11:10', 1, 1, 7.10, 1.40, 3.00, NULL, 94, 269, 3290, 0.00, 28.7, 792, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (173, '2025-06-17 11:30:10', 1, 1, 7.70, 1.90, 0.50, NULL, 118, 289, 3948, 0.00, 26.9, 754, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (174, '2025-06-16 11:34:10', 1, 1, 7.20, 1.80, 1.10, NULL, 109, 251, 3856, 0.10, 29.3, 799, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (175, '2025-06-15 08:04:10', 1, 1, 7.00, 1.60, 1.50, NULL, 120, 256, 3045, 0.40, 26.4, 661, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (176, '2025-06-14 08:34:10', 1, 1, 7.20, 1.90, 0.90, NULL, 106, 398, 3299, 0.90, 26.3, 761, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (177, '2025-06-13 11:37:10', 1, 1, 7.50, 1.40, 2.10, NULL, 89, 277, 3255, 0.80, 27.4, 655, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (178, '2025-06-12 10:31:10', 1, 1, 7.70, 2.80, 2.30, NULL, 114, 386, 3274, 0.50, 28.0, 654, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (179, '2025-06-11 10:35:10', 1, 1, 7.30, 1.10, 1.10, NULL, 95, 307, 3234, 0.30, 28.5, 734, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (180, '2025-06-10 10:24:10', 1, 1, 7.30, 0.50, 1.70, NULL, 108, 379, 3798, 0.40, 29.2, 720, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (181, '2025-06-09 10:42:10', 1, 1, 7.20, 1.10, 3.20, NULL, 85, 366, 3082, 0.40, 26.4, 755, 'Routine monthly check', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (182, '2025-06-08 10:42:10', 1, 1, 7.70, 0.60, 1.40, NULL, 100, 209, 3222, 0.20, 26.7, 675, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (183, '2025-06-07 09:47:10', 1, 1, 7.10, 0.50, 2.50, NULL, 110, 253, 3529, 0.50, 26.3, 786, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (184, '2025-06-06 11:52:10', 1, 1, 7.30, 1.70, 2.40, NULL, 89, 221, 3815, 0.70, 28.8, 671, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (185, '2025-06-05 09:52:10', 1, 1, 7.40, 2.70, 1.00, NULL, 85, 225, 3942, 0.90, 26.8, 705, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (186, '2025-06-04 11:28:10', 1, 1, 7.00, 2.10, 2.10, NULL, 98, 357, 3199, 0.90, 29.8, 765, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (187, '2025-06-03 08:39:10', 1, 1, 7.60, 1.40, 2.70, NULL, 83, 307, 3172, 0.60, 26.0, 654, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (188, '2025-06-02 09:14:10', 1, 1, 7.10, 1.60, 3.40, NULL, 86, 296, 3951, 0.70, 26.9, 689, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (189, '2025-06-01 11:39:10', 1, 1, 7.40, 1.60, 2.60, NULL, 91, 355, 3476, 0.60, 28.7, 710, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (190, '2025-05-31 11:14:10', 1, 1, 7.30, 2.00, 3.30, NULL, 88, 316, 3387, 0.70, 29.4, 752, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (191, '2025-05-30 09:40:10', 1, 1, 7.50, 1.50, 1.30, NULL, 109, 236, 3609, 0.00, 28.6, 658, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (192, '2025-05-29 09:19:10', 1, 1, 7.60, 1.10, 0.90, NULL, 103, 353, 3407, 0.30, 29.5, 696, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (193, '2025-05-28 09:19:10', 1, 1, 7.00, 2.50, 0.80, NULL, 120, 257, 3000, 0.20, 28.3, 675, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (194, '2025-05-27 11:22:10', 1, 1, 7.20, 0.50, 0.70, NULL, 104, 267, 3349, 0.00, 26.1, 681, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (195, '2025-05-26 09:58:10', 1, 1, 7.80, 0.50, 3.50, NULL, 117, 319, 3541, 0.00, 29.3, 779, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (196, '2025-05-25 10:08:10', 1, 1, 7.00, 2.90, 0.50, NULL, 89, 379, 3094, 0.30, 27.1, 761, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (197, '2025-05-24 09:10:10', 1, 1, 7.20, 1.60, 0.70, NULL, 115, 327, 3159, 0.10, 27.9, 742, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (198, '2025-05-23 10:47:10', 1, 1, 7.00, 1.10, 3.40, NULL, 117, 230, 3062, 0.80, 27.5, 795, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (199, '2025-05-22 09:23:10', 1, 1, 7.00, 0.80, 2.70, NULL, 90, 363, 3605, 0.50, 27.4, 791, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (200, '2025-05-21 10:46:10', 1, 1, 7.80, 2.90, 2.50, NULL, 83, 265, 3536, 0.30, 27.1, 762, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (201, '2025-05-20 08:43:10', 1, 1, 7.30, 1.70, 0.70, NULL, 93, 246, 3609, 0.80, 29.0, 669, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (202, '2025-05-19 11:04:10', 1, 1, 7.40, 2.90, 2.00, NULL, 84, 232, 3368, 0.70, 29.6, 693, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (203, '2025-05-18 10:32:10', 1, 1, 7.80, 2.50, 2.40, NULL, 80, 331, 3848, 0.20, 27.6, 772, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (204, '2025-05-17 09:51:10', 1, 1, 7.40, 2.50, 1.50, NULL, 100, 217, 3386, 0.80, 26.7, 723, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (205, '2025-05-16 08:23:10', 1, 1, 7.00, 2.60, 0.60, NULL, 101, 324, 3969, 0.80, 26.5, 723, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (206, '2025-05-15 08:00:10', 1, 1, 7.80, 1.30, 3.20, NULL, 86, 398, 3293, 0.80, 28.5, 724, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (207, '2025-05-14 10:14:10', 1, 1, 7.60, 2.10, 2.60, NULL, 107, 385, 3326, 0.00, 26.7, 788, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (208, '2025-05-13 08:25:10', 1, 1, 7.50, 1.00, 2.20, NULL, 103, 343, 3257, 0.30, 26.9, 699, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (209, '2025-05-12 09:39:10', 1, 1, 7.60, 1.10, 1.10, NULL, 108, 385, 3879, 0.10, 28.0, 693, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (210, '2025-05-11 08:21:10', 1, 1, 7.00, 1.60, 0.80, NULL, 104, 345, 3558, 0.60, 28.2, 744, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (211, '2025-05-10 11:31:10', 1, 1, 7.80, 2.50, 3.10, NULL, 99, 326, 3883, 0.60, 28.3, 760, 'Routine monthly check', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (212, '2025-05-09 11:33:10', 1, 1, 7.20, 2.50, 3.10, NULL, 104, 306, 3141, 0.60, 26.3, 704, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (213, '2025-05-08 08:14:10', 1, 1, 7.20, 1.60, 2.90, NULL, 86, 224, 4000, 0.30, 27.4, 789, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (214, '2025-05-07 08:34:10', 1, 1, 7.80, 1.10, 1.30, NULL, 94, 397, 3633, 0.10, 27.2, 663, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (215, '2025-05-06 08:08:10', 1, 1, 7.30, 1.30, 1.60, NULL, 118, 321, 3467, 0.30, 27.9, 667, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (216, '2025-05-05 08:27:10', 1, 1, 7.70, 2.80, 0.60, NULL, 83, 327, 3820, 0.40, 27.5, 702, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (217, '2025-05-04 10:31:10', 1, 1, 7.50, 1.60, 2.80, NULL, 111, 353, 3526, 0.10, 26.2, 733, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (218, '2025-05-03 10:57:10', 1, 1, 7.10, 2.30, 3.20, NULL, 118, 329, 3217, 0.90, 26.0, 747, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (219, '2025-05-02 08:04:10', 1, 1, 7.00, 0.50, 3.00, NULL, 110, 210, 3614, 0.40, 26.3, 700, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (220, '2025-05-01 10:04:10', 1, 1, 7.80, 0.50, 2.90, NULL, 113, 262, 3866, 0.80, 26.5, 757, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (221, '2025-04-30 11:26:10', 1, 1, 7.60, 2.90, 2.00, NULL, 96, 248, 3402, 0.30, 27.7, 702, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (222, '2025-04-29 11:32:10', 1, 1, 7.70, 3.00, 2.10, NULL, 91, 283, 3853, 0.50, 27.9, 729, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (223, '2025-04-28 11:32:10', 1, 1, 7.00, 1.20, 3.00, NULL, 118, 346, 3014, 0.40, 26.8, 776, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (224, '2025-04-27 08:39:10', 1, 1, 7.80, 0.60, 1.40, NULL, 87, 362, 3918, 0.50, 29.4, 766, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (225, '2025-04-26 11:49:10', 1, 1, 7.50, 1.30, 2.10, NULL, 103, 399, 3867, 0.50, 26.6, 752, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (226, '2025-04-25 11:03:10', 1, 1, 7.80, 1.80, 1.30, NULL, 95, 343, 3082, 0.60, 26.1, 659, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (227, '2025-04-24 11:25:10', 1, 1, 7.50, 2.20, 1.90, NULL, 84, 299, 3122, 0.70, 27.6, 707, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (228, '2025-04-23 08:31:10', 1, 1, 7.30, 2.60, 0.70, NULL, 87, 331, 3355, 0.10, 28.5, 714, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (229, '2025-04-22 08:46:10', 1, 1, 7.40, 1.50, 1.90, NULL, 115, 247, 3429, 0.20, 27.7, 725, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (230, '2025-04-21 10:22:10', 1, 1, 7.30, 2.60, 1.40, NULL, 92, 239, 3033, 0.30, 26.7, 671, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (231, '2025-04-20 09:58:10', 1, 1, 7.00, 2.00, 2.70, NULL, 117, 285, 3262, 1.00, 27.1, 666, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (232, '2025-04-19 11:09:10', 1, 1, 7.70, 0.90, 0.60, NULL, 109, 388, 3829, 0.60, 29.5, 754, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (233, '2025-04-18 10:26:10', 1, 1, 7.60, 1.50, 0.50, NULL, 106, 217, 3534, 0.90, 29.3, 769, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (234, '2025-04-17 09:27:10', 1, 1, 7.80, 2.80, 0.60, NULL, 111, 219, 3372, 0.00, 29.7, 732, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (235, '2025-04-16 08:38:10', 1, 1, 7.00, 2.50, 1.10, NULL, 112, 313, 3334, 1.00, 28.1, 709, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (236, '2025-04-15 09:08:10', 1, 1, 7.10, 2.00, 2.00, NULL, 80, 337, 3133, 0.30, 28.7, 787, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (237, '2025-04-14 11:14:10', 1, 1, 7.80, 2.70, 3.10, NULL, 115, 213, 3520, 0.10, 27.0, 685, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (238, '2025-04-13 08:38:10', 1, 1, 7.70, 1.70, 1.40, NULL, 116, 340, 3925, 0.20, 28.6, 738, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (239, '2025-04-12 08:05:10', 1, 1, 7.80, 0.50, 2.60, NULL, 101, 318, 3955, 0.60, 29.7, 788, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (240, '2025-04-11 10:08:10', 1, 1, 7.40, 2.70, 1.80, NULL, 119, 206, 3094, 0.20, 28.5, 687, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (241, '2025-04-10 09:55:10', 1, 1, 7.20, 0.50, 1.80, NULL, 113, 285, 3500, 0.70, 29.5, 726, 'Routine monthly check', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (242, '2025-04-09 09:41:10', 1, 1, 7.80, 2.30, 1.80, NULL, 118, 205, 3723, 0.60, 29.4, 717, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (243, '2025-04-08 11:40:10', 1, 1, 7.20, 1.10, 2.90, NULL, 91, 357, 3189, 0.60, 28.9, 665, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (244, '2025-04-07 09:38:10', 1, 1, 7.20, 1.40, 1.80, NULL, 100, 295, 3212, 1.00, 27.7, 760, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (245, '2025-04-06 11:27:10', 1, 1, 7.60, 2.50, 2.80, NULL, 86, 382, 3115, 1.00, 28.5, 761, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (246, '2025-04-05 09:43:10', 1, 1, 7.40, 2.70, 1.70, NULL, 112, 201, 3185, 1.00, 29.3, 787, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (247, '2025-04-04 08:39:10', 1, 1, 7.60, 2.00, 2.80, NULL, 100, 330, 3008, 0.10, 28.1, 659, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (248, '2025-04-03 09:08:10', 1, 1, 7.80, 2.00, 2.90, NULL, 96, 317, 3027, 0.50, 28.5, 661, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (249, '2025-04-02 11:17:10', 1, 1, 7.60, 1.00, 2.00, NULL, 91, 359, 3559, 0.80, 29.9, 661, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (250, '2025-04-01 10:40:10', 1, 1, 7.20, 2.00, 2.80, NULL, 103, 258, 3364, 0.60, 26.6, 759, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (251, '2025-03-31 08:24:10', 1, 1, 7.50, 2.70, 0.60, NULL, 120, 339, 3483, 0.10, 27.1, 800, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (252, '2025-03-30 11:27:10', 1, 1, 7.50, 2.90, 0.80, NULL, 87, 296, 3773, 0.20, 26.9, 675, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (253, '2025-03-29 09:52:10', 1, 1, 7.10, 2.40, 2.10, NULL, 110, 264, 3410, 0.30, 29.4, 693, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (254, '2025-03-28 08:46:10', 1, 1, 7.50, 0.50, 0.70, NULL, 82, 332, 3962, 0.70, 26.1, 675, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (255, '2025-03-27 09:34:10', 1, 1, 7.40, 1.20, 0.60, NULL, 103, 206, 3582, 0.60, 28.1, 742, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (256, '2025-03-26 09:07:10', 1, 1, 7.60, 1.10, 1.90, NULL, 115, 247, 3867, 0.10, 28.9, 724, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (257, '2025-03-25 09:56:10', 1, 1, 7.20, 2.70, 3.00, NULL, 91, 260, 3002, 0.10, 26.0, 759, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (258, '2025-03-24 10:53:10', 1, 1, 7.80, 2.70, 1.20, NULL, 91, 248, 3992, 0.30, 29.2, 719, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (259, '2025-03-23 09:09:10', 1, 1, 7.80, 1.00, 0.90, NULL, 107, 233, 3300, 0.70, 29.1, 695, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (260, '2025-03-22 11:54:10', 1, 1, 7.10, 2.20, 0.50, NULL, 91, 361, 3958, 0.10, 26.6, 704, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (261, '2025-03-21 09:30:10', 1, 1, 7.80, 2.50, 0.50, NULL, 119, 231, 3796, 0.40, 27.9, 686, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (262, '2025-03-20 11:02:10', 1, 1, 7.80, 1.70, 1.60, NULL, 116, 259, 3035, 0.30, 28.1, 687, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (263, '2025-03-19 10:16:10', 1, 1, 7.50, 0.90, 1.50, NULL, 111, 286, 3886, 0.20, 28.0, 798, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (264, '2025-03-18 10:22:10', 1, 1, 7.70, 2.60, 1.90, NULL, 86, 207, 3270, 0.60, 26.9, 722, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (265, '2025-03-17 09:05:10', 1, 1, 7.30, 2.20, 2.40, NULL, 98, 212, 3269, 0.30, 27.9, 726, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (266, '2025-03-16 08:54:10', 1, 1, 7.00, 2.90, 1.60, NULL, 106, 240, 3643, 0.70, 27.5, 696, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (267, '2025-03-15 11:46:10', 1, 1, 7.20, 2.50, 1.40, NULL, 113, 276, 3870, 0.90, 30.0, 769, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (268, '2025-03-14 10:41:10', 1, 1, 7.80, 1.80, 3.50, NULL, 92, 308, 3462, 0.60, 26.3, 766, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (269, '2025-03-13 10:52:10', 1, 1, 7.80, 2.30, 0.90, NULL, 96, 212, 3892, 0.30, 29.0, 781, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (270, '2025-03-12 08:56:10', 1, 1, 7.60, 0.80, 0.50, NULL, 116, 379, 3097, 0.70, 27.3, 779, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (271, '2025-03-11 08:43:10', 1, 1, 7.00, 2.80, 0.50, NULL, 114, 389, 3153, 0.40, 27.3, 698, 'Routine monthly check', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (272, '2025-03-10 09:46:10', 1, 1, 7.20, 0.50, 0.80, NULL, 118, 316, 3555, 0.80, 28.3, 662, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (273, '2025-03-09 11:22:10', 1, 1, 7.70, 1.40, 2.00, NULL, 114, 276, 3768, 0.10, 29.3, 794, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (274, '2025-03-08 08:26:10', 1, 1, 7.20, 2.00, 0.90, NULL, 102, 331, 3293, 0.70, 29.4, 669, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (275, '2025-03-07 11:12:10', 1, 1, 7.70, 2.00, 0.90, NULL, 107, 213, 3169, 0.70, 29.7, 786, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (276, '2025-03-06 09:31:10', 1, 1, 7.70, 2.80, 1.10, NULL, 94, 393, 3736, 1.00, 27.1, 678, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (277, '2025-03-05 08:24:10', 1, 1, 7.80, 0.60, 1.70, NULL, 119, 300, 3606, 0.40, 26.2, 754, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (278, '2025-03-04 11:34:10', 1, 1, 7.20, 2.90, 2.50, NULL, 93, 318, 3411, 0.80, 26.9, 679, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (279, '2025-03-03 08:26:10', 1, 1, 7.30, 0.70, 0.50, NULL, 118, 284, 3177, 0.70, 28.2, 725, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (280, '2025-03-02 09:46:10', 1, 1, 7.00, 1.80, 3.20, NULL, 82, 387, 3343, 0.90, 28.7, 724, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (281, '2025-03-01 10:45:10', 1, 1, 7.40, 0.60, 3.40, NULL, 105, 314, 3895, 0.50, 28.8, 659, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (282, '2025-02-28 11:04:10', 1, 1, 7.00, 1.90, 0.50, NULL, 105, 227, 3796, 0.60, 28.5, 686, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (283, '2025-02-27 08:57:10', 1, 1, 7.80, 1.50, 2.90, NULL, 115, 398, 3429, 0.50, 28.1, 670, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (284, '2025-02-26 09:46:10', 1, 1, 7.60, 1.40, 2.50, NULL, 87, 248, 3288, 0.30, 28.2, 745, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (285, '2025-02-25 08:22:10', 1, 1, 7.70, 1.00, 3.20, NULL, 100, 363, 3504, 0.50, 28.1, 713, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (286, '2025-02-24 09:42:10', 1, 1, 7.30, 2.90, 2.50, NULL, 80, 387, 3941, 0.50, 28.4, 765, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (287, '2025-02-23 09:23:10', 1, 1, 7.50, 1.90, 3.00, NULL, 95, 246, 3145, 0.60, 27.3, 671, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (288, '2025-02-22 11:38:10', 1, 1, 7.80, 2.60, 2.80, NULL, 116, 271, 3950, 0.90, 29.9, 712, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (289, '2025-02-21 08:15:10', 1, 1, 7.50, 1.70, 1.00, NULL, 99, 236, 3591, 1.00, 26.1, 730, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (290, '2025-02-20 08:00:10', 1, 1, 7.40, 1.70, 3.30, NULL, 120, 360, 3965, 0.00, 29.0, 738, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (291, '2025-02-19 11:56:10', 1, 1, 7.70, 2.30, 1.10, NULL, 115, 310, 3889, 0.40, 27.8, 708, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (292, '2025-02-18 08:05:10', 1, 1, 7.10, 1.90, 1.00, NULL, 95, 265, 3779, 0.40, 27.6, 696, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (293, '2025-02-17 08:10:10', 1, 1, 7.10, 1.80, 2.40, NULL, 84, 265, 3154, 0.20, 26.8, 750, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (294, '2025-02-16 09:25:10', 1, 1, 7.30, 0.80, 3.00, NULL, 81, 302, 3280, 0.70, 27.7, 791, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (295, '2025-02-15 08:41:10', 1, 1, 7.60, 1.70, 3.20, NULL, 97, 209, 3053, 0.40, 26.8, 662, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (296, '2025-02-14 08:23:10', 1, 1, 7.40, 2.00, 2.50, NULL, 100, 273, 3759, 0.80, 26.2, 719, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (297, '2025-02-13 09:03:10', 1, 1, 7.50, 1.90, 2.20, NULL, 101, 368, 3167, 1.00, 29.2, 715, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (298, '2025-02-12 11:06:10', 1, 1, 7.30, 3.00, 1.70, NULL, 109, 295, 3521, 0.20, 26.6, 662, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (299, '2025-02-11 08:21:10', 1, 1, 7.50, 1.50, 1.20, NULL, 101, 280, 3037, 0.70, 26.7, 719, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (300, '2025-02-10 11:59:10', 1, 1, 7.40, 2.00, 1.90, NULL, 110, 288, 3889, 0.90, 28.3, 748, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (301, '2025-02-09 09:44:10', 1, 1, 7.00, 2.20, 2.20, NULL, 109, 235, 3629, 0.70, 28.3, 790, 'Routine monthly check', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (302, '2025-02-08 08:16:10', 1, 1, 7.80, 2.30, 1.10, NULL, 109, 267, 3527, 0.60, 26.6, 653, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (303, '2025-02-07 08:28:10', 1, 1, 7.20, 2.80, 3.50, NULL, 120, 302, 3807, 0.20, 27.2, 776, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (304, '2025-02-06 09:19:10', 1, 1, 7.30, 2.30, 2.50, NULL, 114, 263, 3086, 0.90, 26.6, 698, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (305, '2025-02-05 10:36:10', 1, 1, 7.20, 0.80, 1.50, NULL, 91, 275, 3164, 0.00, 28.6, 783, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (306, '2025-02-04 09:35:10', 1, 1, 7.50, 1.00, 1.40, NULL, 97, 391, 3844, 1.00, 29.0, 670, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (307, '2025-02-03 11:52:10', 1, 1, 7.60, 1.60, 0.90, NULL, 102, 302, 3056, 0.60, 27.9, 658, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (308, '2025-02-02 10:33:10', 1, 1, 7.00, 2.70, 1.10, NULL, 100, 220, 3544, 0.80, 28.5, 653, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (309, '2025-02-01 09:26:10', 1, 1, 7.00, 1.90, 0.70, NULL, 112, 262, 3190, 0.80, 27.5, 704, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (310, '2025-01-31 08:44:10', 1, 1, 7.60, 2.00, 2.00, NULL, 111, 346, 3698, 0.10, 27.9, 738, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (311, '2025-01-30 09:05:10', 1, 1, 7.30, 2.20, 2.70, NULL, 89, 386, 3689, 1.00, 29.7, 766, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (312, '2025-01-29 10:26:10', 1, 1, 7.70, 0.80, 2.90, NULL, 89, 231, 3772, 0.20, 29.7, 720, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (313, '2025-01-28 09:42:10', 1, 1, 7.60, 2.30, 1.50, NULL, 91, 219, 3335, 1.00, 28.3, 651, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (314, '2025-01-27 08:29:10', 1, 1, 7.70, 2.70, 0.60, NULL, 107, 281, 3708, 0.20, 26.8, 748, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (315, '2025-01-26 11:47:10', 1, 1, 7.80, 2.60, 0.90, NULL, 107, 272, 3162, 1.00, 28.5, 715, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (316, '2025-01-25 08:35:10', 1, 1, 7.20, 2.00, 1.80, NULL, 118, 356, 3576, 0.40, 28.4, 723, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (317, '2025-01-24 10:00:10', 1, 1, 7.40, 1.70, 1.20, NULL, 101, 293, 3911, 0.20, 27.6, 725, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (318, '2025-01-23 09:21:10', 1, 1, 7.20, 1.70, 1.10, NULL, 99, 304, 3582, 1.00, 28.6, 733, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (319, '2025-01-22 11:49:10', 1, 1, 7.10, 2.60, 0.70, NULL, 93, 217, 3021, 0.90, 27.9, 749, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (320, '2025-01-21 09:00:10', 1, 1, 7.40, 1.70, 3.50, NULL, 81, 295, 3627, 1.00, 28.5, 681, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (321, '2025-01-20 11:11:10', 1, 1, 7.50, 1.50, 3.10, NULL, 113, 345, 3588, 0.00, 29.1, 764, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (322, '2025-01-19 10:08:10', 1, 1, 7.70, 1.40, 2.20, NULL, 120, 252, 3114, 0.30, 29.0, 734, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (323, '2025-01-18 10:57:10', 1, 1, 7.10, 3.00, 1.90, NULL, 80, 283, 3753, 0.70, 27.4, 786, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (324, '2025-01-17 10:49:10', 1, 1, 7.10, 1.40, 1.30, NULL, 103, 321, 3950, 0.70, 29.7, 794, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (325, '2025-01-16 10:53:10', 1, 1, 7.40, 0.80, 2.20, NULL, 83, 362, 3932, 0.70, 28.7, 659, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (326, '2025-01-15 09:55:10', 1, 1, 7.60, 0.70, 0.70, NULL, 110, 399, 3831, 0.00, 28.8, 788, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (327, '2025-01-14 11:31:10', 1, 1, 7.10, 0.90, 1.20, NULL, 120, 242, 3797, 0.70, 27.2, 738, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (328, '2025-01-13 08:33:10', 1, 1, 7.40, 1.20, 3.10, NULL, 90, 321, 3055, 1.00, 26.8, 664, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (329, '2025-01-12 09:07:10', 1, 1, 7.10, 1.30, 3.00, NULL, 89, 224, 3977, 0.20, 29.2, 716, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (330, '2025-01-11 08:42:10', 1, 1, 7.80, 0.60, 1.70, NULL, 88, 308, 3705, 0.90, 29.6, 693, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (331, '2025-01-10 10:16:10', 1, 1, 7.30, 0.90, 2.60, NULL, 92, 216, 3667, 0.50, 28.4, 716, 'Routine monthly check', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (332, '2025-01-09 09:56:10', 1, 1, 7.30, 1.80, 2.40, NULL, 104, 318, 3162, 0.00, 27.8, 721, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (333, '2025-01-08 09:39:10', 1, 1, 7.70, 2.60, 3.10, NULL, 81, 359, 3134, 1.00, 26.1, 787, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (334, '2025-01-07 10:23:10', 1, 1, 7.10, 1.50, 2.30, NULL, 83, 265, 3353, 0.20, 26.5, 704, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (335, '2025-01-06 10:48:10', 1, 1, 7.10, 1.30, 2.70, NULL, 101, 256, 3692, 0.40, 27.4, 690, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (336, '2025-01-05 08:27:10', 1, 1, 7.50, 2.40, 3.10, NULL, 97, 241, 3553, 0.00, 28.2, 784, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (337, '2025-01-04 11:43:10', 1, 1, 7.30, 2.50, 1.40, NULL, 115, 379, 3218, 0.50, 29.8, 665, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (338, '2025-01-03 08:28:10', 1, 1, 7.00, 0.90, 1.00, NULL, 105, 373, 3977, 1.00, 29.9, 659, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (339, '2025-01-02 09:50:10', 1, 1, 7.10, 2.30, 2.00, NULL, 89, 382, 3883, 0.90, 26.6, 718, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (340, '2025-01-01 11:27:10', 1, 1, 7.30, 2.10, 2.60, NULL, 80, 364, 3353, 0.00, 26.5, 702, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (341, '2024-12-31 09:04:10', 1, 1, 7.70, 1.50, 2.30, NULL, 102, 239, 3543, 0.10, 29.9, 701, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (342, '2024-12-30 09:11:10', 1, 1, 7.10, 1.50, 1.00, NULL, 88, 318, 3857, 0.80, 26.9, 777, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (343, '2024-12-29 10:52:10', 1, 1, 7.00, 2.30, 3.40, NULL, 112, 339, 3428, 1.00, 26.2, 723, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (344, '2024-12-28 11:38:10', 1, 1, 7.70, 0.60, 2.60, NULL, 97, 385, 3166, 0.00, 28.2, 700, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (345, '2024-12-27 10:23:10', 1, 1, 7.10, 1.10, 2.30, NULL, 104, 254, 3795, 0.20, 29.3, 692, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (346, '2024-12-26 10:44:10', 1, 1, 7.60, 0.60, 2.70, NULL, 102, 208, 3129, 0.60, 29.8, 744, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (347, '2024-12-25 09:40:10', 1, 1, 7.70, 2.30, 1.10, NULL, 84, 315, 3180, 0.00, 28.0, 684, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (348, '2024-12-24 10:04:10', 1, 1, 7.80, 2.70, 0.80, NULL, 86, 205, 3389, 0.40, 28.8, 781, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (349, '2024-12-23 11:53:10', 1, 1, 7.40, 1.30, 1.90, NULL, 93, 327, 3559, 0.40, 27.1, 769, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (350, '2024-12-22 08:01:10', 1, 1, 7.40, 1.40, 0.90, NULL, 91, 365, 3420, 0.00, 26.0, 722, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (351, '2024-12-21 10:52:10', 1, 1, 7.80, 3.00, 1.20, NULL, 120, 400, 3491, 0.10, 29.8, 687, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (352, '2024-12-20 11:43:10', 1, 1, 7.30, 0.80, 2.90, NULL, 102, 269, 3408, 0.00, 29.4, 668, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (353, '2024-12-19 08:20:10', 1, 1, 7.30, 2.80, 1.10, NULL, 83, 267, 3143, 0.60, 26.2, 747, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (354, '2024-12-18 11:09:10', 1, 1, 7.40, 2.60, 2.80, NULL, 104, 314, 3406, 0.20, 28.6, 738, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (355, '2024-12-17 08:58:10', 1, 1, 7.20, 0.90, 0.60, NULL, 108, 325, 3264, 0.30, 30.0, 678, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (356, '2024-12-16 11:01:10', 1, 1, 7.70, 1.80, 3.20, NULL, 114, 362, 3960, 1.00, 28.9, 660, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (357, '2024-12-15 11:48:10', 1, 1, 7.20, 2.40, 3.20, NULL, 91, 373, 3391, 0.50, 27.7, 753, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (358, '2024-12-14 10:52:10', 1, 1, 7.60, 0.90, 1.40, NULL, 84, 359, 3132, 0.90, 27.9, 658, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (359, '2024-12-13 11:58:10', 1, 1, 7.30, 1.90, 0.60, NULL, 115, 333, 3105, 0.90, 27.7, 679, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (360, '2024-12-12 08:21:10', 1, 1, 7.30, 1.60, 3.10, NULL, 117, 350, 3456, 0.00, 29.0, 763, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (361, '2024-12-11 11:20:10', 1, 1, 7.40, 0.50, 1.70, NULL, 91, 237, 3536, 1.00, 29.9, 784, 'Routine monthly check', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (362, '2024-12-10 09:00:10', 1, 1, 7.50, 1.50, 2.90, NULL, 112, 260, 3576, 0.80, 26.2, 794, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (363, '2024-12-09 08:23:10', 1, 1, 7.50, 1.90, 1.80, NULL, 115, 394, 3235, 0.50, 29.9, 732, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (364, '2024-12-08 09:40:10', 1, 1, 7.10, 2.60, 1.60, NULL, 107, 330, 3588, 0.10, 27.8, 755, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (365, '2024-12-07 08:24:10', 1, 1, 7.60, 1.50, 3.10, NULL, 80, 279, 3508, 0.40, 27.4, 693, NULL, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (366, '2025-12-06 09:17:11', 1, 2, 7.50, 1.30, 3.20, NULL, 120, 282, 3731, 0.30, 26.4, 791, 'Routine monthly check', '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (367, '2025-12-05 09:24:11', 1, 2, 7.60, 2.50, 1.30, NULL, 106, 329, 3566, 0.70, 26.7, 708, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (368, '2025-12-04 11:57:11', 1, 2, 7.60, 0.70, 2.10, NULL, 109, 316, 3606, 0.30, 26.6, 723, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (369, '2025-12-03 10:48:11', 1, 2, 7.20, 0.80, 1.20, NULL, 109, 229, 3712, 0.30, 26.1, 737, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (370, '2025-12-02 08:59:11', 1, 2, 7.30, 0.70, 1.00, NULL, 109, 372, 3812, 1.00, 28.5, 786, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (371, '2025-12-01 09:34:11', 1, 2, 7.30, 1.00, 0.60, NULL, 105, 205, 3091, 0.40, 29.1, 747, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (372, '2025-11-30 08:01:11', 1, 2, 7.60, 1.30, 1.80, NULL, 109, 282, 3580, 0.40, 26.9, 695, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (373, '2025-11-29 10:17:11', 1, 2, 7.20, 0.80, 0.70, NULL, 98, 338, 3409, 0.80, 27.8, 789, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (374, '2025-11-28 09:26:11', 1, 2, 7.40, 1.00, 1.00, NULL, 85, 362, 3040, 0.90, 28.3, 800, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (375, '2025-11-27 08:03:11', 1, 2, 7.10, 1.50, 0.70, NULL, 116, 215, 3402, 1.00, 29.4, 784, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (376, '2025-11-26 09:37:11', 1, 2, 7.10, 0.50, 0.50, NULL, 90, 232, 3620, 0.70, 26.1, 722, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (377, '2025-11-25 09:40:11', 1, 2, 7.40, 3.00, 0.70, NULL, 105, 216, 3720, 0.10, 26.5, 783, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (378, '2025-11-24 08:06:11', 1, 2, 7.40, 0.60, 2.60, NULL, 100, 313, 3718, 0.80, 28.7, 795, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (379, '2025-11-23 09:00:11', 1, 2, 7.30, 1.60, 3.10, NULL, 94, 315, 3067, 0.90, 28.8, 786, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (380, '2025-11-22 08:14:11', 1, 2, 7.00, 0.50, 1.10, NULL, 84, 248, 3830, 0.40, 27.6, 785, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (381, '2025-11-21 09:36:11', 1, 2, 7.30, 2.90, 2.90, NULL, 107, 348, 3606, 0.90, 28.6, 684, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (382, '2025-11-20 11:34:11', 1, 2, 7.60, 0.60, 0.60, NULL, 84, 330, 3551, 1.00, 27.3, 777, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (383, '2025-11-19 09:39:11', 1, 2, 7.50, 2.40, 0.90, NULL, 102, 287, 3822, 0.20, 27.6, 698, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (384, '2025-11-18 09:38:11', 1, 2, 7.20, 1.70, 1.30, NULL, 94, 289, 3520, 0.70, 29.5, 653, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (385, '2025-11-17 10:34:11', 1, 2, 7.80, 1.00, 2.70, NULL, 101, 245, 3796, 0.00, 27.7, 682, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (386, '2025-11-16 10:21:11', 1, 2, 7.20, 1.30, 2.50, NULL, 114, 390, 3897, 0.90, 27.3, 738, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (387, '2025-11-15 11:46:11', 1, 2, 7.60, 0.50, 0.60, NULL, 104, 335, 3483, 0.10, 26.0, 678, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (388, '2025-11-14 11:34:11', 1, 2, 7.30, 1.60, 2.00, NULL, 97, 397, 3238, 0.50, 28.5, 752, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (389, '2025-11-13 08:44:11', 1, 2, 7.10, 1.00, 3.40, NULL, 92, 311, 3159, 0.20, 28.5, 708, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (390, '2025-11-12 09:24:11', 1, 2, 7.60, 2.80, 2.20, NULL, 114, 372, 3499, 0.00, 30.0, 692, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (391, '2025-11-11 11:35:11', 1, 2, 7.20, 0.80, 2.70, NULL, 105, 389, 3379, 0.70, 28.9, 730, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (392, '2025-11-10 09:14:11', 1, 2, 7.30, 1.80, 1.00, NULL, 107, 268, 3790, 0.70, 26.8, 658, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (393, '2025-11-09 09:56:11', 1, 2, 7.60, 2.80, 2.10, NULL, 80, 389, 3203, 0.60, 28.5, 755, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (394, '2025-11-08 11:40:11', 1, 2, 7.80, 1.60, 0.80, NULL, 92, 213, 3263, 0.10, 26.0, 651, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (395, '2025-11-07 08:14:11', 1, 2, 7.50, 0.60, 1.50, NULL, 91, 380, 3919, 0.00, 29.9, 798, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (396, '2025-11-06 10:35:11', 1, 2, 7.80, 0.80, 3.50, NULL, 102, 287, 3515, 1.00, 29.0, 730, 'Routine monthly check', '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (397, '2025-11-05 11:30:11', 1, 2, 7.50, 2.50, 2.80, NULL, 115, 344, 3161, 0.20, 26.6, 658, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (398, '2025-11-04 11:44:11', 1, 2, 7.80, 2.90, 1.60, NULL, 84, 368, 3645, 0.10, 26.2, 773, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (399, '2025-11-03 09:43:11', 1, 2, 7.20, 2.10, 2.00, NULL, 111, 363, 3734, 0.00, 27.8, 716, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (400, '2025-11-02 10:54:11', 1, 2, 7.60, 1.50, 3.30, NULL, 100, 361, 3439, 0.70, 29.2, 700, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (401, '2025-11-01 10:25:11', 1, 2, 7.20, 1.00, 3.20, NULL, 90, 286, 3707, 0.90, 26.6, 767, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (402, '2025-10-31 10:47:11', 1, 2, 7.50, 0.60, 3.20, NULL, 101, 251, 3986, 0.40, 28.3, 651, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (403, '2025-10-30 08:07:11', 1, 2, 7.40, 1.40, 1.70, NULL, 106, 396, 3718, 0.20, 30.0, 688, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (404, '2025-10-29 11:39:11', 1, 2, 7.20, 1.30, 0.60, NULL, 95, 300, 3940, 0.10, 28.8, 659, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (405, '2025-10-28 11:59:11', 1, 2, 7.30, 0.70, 0.90, NULL, 118, 254, 3432, 0.30, 26.2, 680, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (406, '2025-10-27 11:46:11', 1, 2, 7.70, 1.20, 0.70, NULL, 115, 218, 3522, 0.40, 27.6, 724, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (407, '2025-10-26 08:15:11', 1, 2, 7.00, 1.90, 2.90, NULL, 102, 302, 3579, 0.40, 26.5, 682, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (408, '2025-10-25 09:42:11', 1, 2, 7.60, 2.40, 1.80, NULL, 112, 272, 3332, 0.40, 26.5, 797, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (409, '2025-10-24 11:48:11', 1, 2, 7.60, 1.60, 1.20, NULL, 80, 217, 3031, 0.00, 26.1, 661, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (410, '2025-10-23 08:14:11', 1, 2, 7.20, 0.60, 2.90, NULL, 105, 322, 3237, 0.80, 26.3, 745, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (411, '2025-10-22 11:00:11', 1, 2, 7.30, 1.80, 0.60, NULL, 117, 212, 3698, 0.00, 26.3, 687, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (412, '2025-10-21 08:36:11', 1, 2, 7.50, 0.90, 0.50, NULL, 94, 269, 3204, 0.40, 29.2, 781, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (413, '2025-10-20 10:54:11', 1, 2, 7.00, 2.70, 1.10, NULL, 89, 392, 3236, 0.00, 26.7, 744, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (414, '2025-10-19 08:11:11', 1, 2, 7.80, 1.40, 2.60, NULL, 103, 322, 3242, 0.20, 28.8, 774, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (415, '2025-10-18 10:06:11', 1, 2, 7.30, 0.80, 2.50, NULL, 116, 221, 3876, 0.40, 29.4, 709, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (416, '2025-10-17 11:18:11', 1, 2, 7.40, 1.90, 2.00, NULL, 116, 210, 3911, 0.30, 26.3, 736, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (417, '2025-10-16 08:00:11', 1, 2, 7.00, 2.00, 3.00, NULL, 98, 206, 3663, 1.00, 29.9, 663, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (418, '2025-10-15 09:16:11', 1, 2, 7.10, 1.50, 0.50, NULL, 85, 356, 3214, 0.80, 28.8, 681, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (419, '2025-10-14 09:41:11', 1, 2, 7.70, 2.30, 3.40, NULL, 86, 200, 3652, 0.90, 27.5, 725, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (420, '2025-10-13 08:10:11', 1, 2, 7.60, 1.50, 0.90, NULL, 112, 260, 3764, 0.00, 28.0, 795, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (421, '2025-10-12 10:38:11', 1, 2, 7.40, 1.40, 3.40, NULL, 104, 323, 3825, 0.80, 29.9, 703, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (422, '2025-10-11 09:07:11', 1, 2, 7.50, 2.10, 3.10, NULL, 85, 266, 3219, 0.60, 27.7, 714, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (423, '2025-10-10 09:54:11', 1, 2, 7.60, 1.60, 2.00, NULL, 90, 233, 3747, 0.50, 29.0, 651, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (424, '2025-10-09 11:34:11', 1, 2, 7.70, 2.00, 1.10, NULL, 111, 356, 3398, 0.20, 27.4, 677, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (425, '2025-10-08 10:06:11', 1, 2, 7.80, 2.10, 0.90, NULL, 111, 392, 3506, 0.20, 27.8, 764, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (426, '2025-10-07 10:51:11', 1, 2, 7.00, 0.50, 1.40, NULL, 117, 286, 3342, 0.10, 26.6, 737, 'Routine monthly check', '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (427, '2025-10-06 10:32:11', 1, 2, 7.80, 2.50, 2.20, NULL, 94, 219, 3203, 0.10, 26.6, 690, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (428, '2025-10-05 08:59:11', 1, 2, 7.50, 2.10, 1.20, NULL, 100, 304, 3491, 0.80, 29.7, 772, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (429, '2025-10-04 08:47:11', 1, 2, 7.30, 2.40, 2.10, NULL, 108, 261, 3235, 0.10, 26.9, 787, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (430, '2025-10-03 11:55:11', 1, 2, 7.60, 1.50, 1.10, NULL, 112, 342, 3930, 0.00, 26.6, 732, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (431, '2025-10-02 09:03:11', 1, 2, 7.80, 1.20, 1.40, NULL, 105, 380, 3043, 0.70, 26.8, 730, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (432, '2025-10-01 11:37:11', 1, 2, 7.10, 1.80, 1.40, NULL, 90, 388, 3297, 0.20, 28.6, 747, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (433, '2025-09-30 10:17:11', 1, 2, 7.20, 2.10, 1.50, NULL, 86, 253, 3592, 0.20, 28.8, 796, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (434, '2025-09-29 11:52:11', 1, 2, 7.40, 0.70, 3.50, NULL, 108, 267, 3645, 0.20, 28.7, 654, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (435, '2025-09-28 11:42:11', 1, 2, 7.40, 0.70, 1.10, NULL, 116, 373, 3877, 0.20, 29.9, 723, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (436, '2025-09-27 10:08:11', 1, 2, 7.60, 1.60, 3.40, NULL, 91, 316, 3088, 0.80, 29.7, 775, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (437, '2025-09-26 09:59:11', 1, 2, 7.10, 3.00, 1.40, NULL, 105, 320, 3393, 0.20, 26.2, 693, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (438, '2025-09-25 08:13:11', 1, 2, 7.70, 1.00, 0.80, NULL, 110, 370, 3071, 0.80, 27.5, 721, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (439, '2025-09-24 09:55:11', 1, 2, 7.20, 0.80, 1.00, NULL, 107, 392, 3078, 0.10, 27.6, 731, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (440, '2025-09-23 08:39:11', 1, 2, 7.30, 2.40, 2.70, NULL, 100, 236, 3651, 0.10, 29.3, 732, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (441, '2025-09-22 09:21:11', 1, 2, 7.30, 2.20, 0.50, NULL, 106, 256, 3798, 0.60, 28.3, 720, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (442, '2025-09-21 10:33:11', 1, 2, 7.40, 2.40, 0.90, NULL, 91, 383, 3698, 0.90, 28.9, 656, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (443, '2025-09-20 08:54:11', 1, 2, 7.60, 1.20, 1.30, NULL, 106, 272, 3546, 0.80, 29.7, 669, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (444, '2025-09-19 08:46:11', 1, 2, 7.10, 2.00, 3.40, NULL, 91, 227, 3879, 0.60, 29.9, 691, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (445, '2025-09-18 08:29:11', 1, 2, 7.10, 1.20, 2.60, NULL, 113, 333, 3403, 0.90, 29.9, 775, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (446, '2025-09-17 11:49:11', 1, 2, 7.50, 2.20, 1.10, NULL, 86, 204, 3032, 0.00, 27.1, 664, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (447, '2025-09-16 11:51:11', 1, 2, 7.40, 2.50, 1.60, NULL, 82, 334, 3463, 0.00, 29.3, 782, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (448, '2025-09-15 08:40:11', 1, 2, 7.30, 1.00, 2.90, NULL, 89, 216, 3868, 0.70, 27.5, 796, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (449, '2025-09-14 10:11:11', 1, 2, 7.30, 1.50, 3.10, NULL, 120, 271, 3449, 0.00, 28.1, 767, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (450, '2025-09-13 08:51:11', 1, 2, 7.10, 2.50, 1.70, NULL, 99, 398, 3584, 0.90, 27.1, 704, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (451, '2025-09-12 11:51:11', 1, 2, 7.10, 2.90, 1.50, NULL, 110, 339, 3537, 0.50, 27.0, 730, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (452, '2025-09-11 09:21:11', 1, 2, 7.80, 2.10, 0.80, NULL, 93, 356, 3665, 0.90, 29.8, 792, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (453, '2025-09-10 08:14:11', 1, 2, 7.30, 1.10, 3.00, NULL, 117, 368, 3570, 1.00, 26.8, 776, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (454, '2025-09-09 10:26:11', 1, 2, 7.60, 2.50, 2.20, NULL, 105, 368, 3191, 0.20, 29.6, 786, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (455, '2025-09-08 08:19:11', 1, 2, 7.10, 2.10, 2.60, NULL, 102, 376, 3077, 0.60, 29.8, 671, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (456, '2025-09-07 08:28:11', 1, 2, 7.50, 1.90, 1.00, NULL, 96, 341, 3944, 0.60, 28.3, 784, 'Routine monthly check', '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (457, '2025-09-06 10:18:11', 1, 2, 7.00, 3.00, 2.20, NULL, 88, 359, 3403, 0.80, 28.3, 710, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (458, '2025-09-05 08:22:11', 1, 2, 7.60, 1.60, 1.50, NULL, 93, 368, 3173, 0.50, 26.7, 795, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (459, '2025-09-04 10:56:11', 1, 2, 7.20, 1.70, 3.10, NULL, 88, 224, 3213, 0.30, 27.9, 695, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (460, '2025-09-03 11:53:11', 1, 2, 7.50, 0.60, 2.20, NULL, 108, 213, 3635, 0.80, 27.0, 676, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (461, '2025-09-02 08:25:11', 1, 2, 7.00, 2.90, 1.60, NULL, 119, 376, 3157, 0.40, 26.8, 656, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (462, '2025-09-01 11:38:11', 1, 2, 7.10, 2.50, 2.70, NULL, 91, 376, 3180, 0.10, 26.7, 792, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (463, '2025-08-31 08:48:11', 1, 2, 7.00, 1.30, 3.20, NULL, 91, 333, 3951, 0.10, 28.8, 740, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (464, '2025-08-30 10:08:11', 1, 2, 7.60, 0.90, 2.10, NULL, 115, 222, 3966, 0.30, 26.5, 712, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (465, '2025-08-29 09:23:11', 1, 2, 7.50, 1.50, 1.60, NULL, 120, 217, 3269, 0.50, 27.2, 757, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (466, '2025-08-28 10:18:11', 1, 2, 7.10, 2.00, 2.70, NULL, 88, 317, 3987, 0.70, 27.3, 650, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (467, '2025-08-27 08:31:11', 1, 2, 7.60, 0.70, 2.90, NULL, 98, 222, 3642, 0.40, 27.3, 722, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (468, '2025-08-26 10:52:11', 1, 2, 7.20, 2.30, 1.90, NULL, 97, 367, 3408, 0.50, 29.5, 714, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (469, '2025-08-25 11:38:11', 1, 2, 7.80, 0.80, 2.30, NULL, 80, 302, 3737, 0.10, 26.3, 796, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (470, '2025-08-24 09:13:11', 1, 2, 7.50, 2.90, 0.80, NULL, 118, 204, 3633, 0.40, 28.0, 763, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (471, '2025-08-23 11:57:11', 1, 2, 7.50, 1.60, 1.30, NULL, 93, 273, 3981, 0.80, 26.5, 753, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (472, '2025-08-22 09:36:11', 1, 2, 7.20, 2.30, 0.80, NULL, 119, 317, 3329, 0.20, 29.4, 778, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (473, '2025-08-21 11:54:11', 1, 2, 7.20, 1.50, 2.40, NULL, 85, 306, 3435, 1.00, 28.1, 760, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (474, '2025-08-20 11:14:11', 1, 2, 7.20, 1.10, 3.50, NULL, 92, 358, 3585, 0.30, 26.4, 778, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (475, '2025-08-19 10:43:11', 1, 2, 7.60, 1.50, 2.00, NULL, 115, 381, 3825, 1.00, 26.1, 734, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (476, '2025-08-18 09:07:11', 1, 2, 7.10, 1.10, 2.20, NULL, 115, 244, 3007, 0.30, 26.4, 749, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (477, '2025-08-17 10:07:11', 1, 2, 7.40, 2.70, 1.90, NULL, 81, 384, 3349, 1.00, 28.5, 717, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (478, '2025-08-16 10:52:11', 1, 2, 7.60, 2.00, 1.30, NULL, 83, 343, 3624, 0.90, 26.1, 771, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (479, '2025-08-15 09:22:11', 1, 2, 7.80, 1.40, 2.80, NULL, 113, 226, 3104, 0.60, 26.2, 728, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (480, '2025-08-14 09:05:11', 1, 2, 7.70, 1.90, 3.50, NULL, 119, 277, 3864, 0.40, 28.4, 756, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (481, '2025-08-13 11:11:11', 1, 2, 7.10, 3.00, 3.30, NULL, 83, 303, 3723, 0.70, 26.5, 733, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (482, '2025-08-12 10:35:11', 1, 2, 7.50, 2.40, 1.00, NULL, 119, 284, 3729, 0.60, 28.0, 734, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (483, '2025-08-11 08:03:11', 1, 2, 7.20, 2.30, 2.70, NULL, 105, 227, 3402, 0.80, 27.8, 739, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (484, '2025-08-10 11:57:11', 1, 2, 7.50, 1.50, 3.50, NULL, 97, 321, 3187, 0.20, 29.9, 775, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (485, '2025-08-09 10:05:11', 1, 2, 7.80, 0.60, 0.70, NULL, 100, 365, 3684, 0.60, 28.0, 705, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (486, '2025-08-08 09:47:11', 1, 2, 7.20, 3.00, 1.80, NULL, 83, 215, 3145, 0.00, 27.9, 718, 'Routine monthly check', '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (487, '2025-08-07 09:34:11', 1, 2, 7.00, 2.30, 1.70, NULL, 119, 273, 3551, 1.00, 29.2, 774, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (488, '2025-08-06 10:57:11', 1, 2, 7.50, 2.20, 2.80, NULL, 106, 337, 3308, 0.80, 27.9, 730, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (489, '2025-08-05 08:20:11', 1, 2, 7.20, 1.40, 2.00, NULL, 84, 326, 3476, 0.50, 26.1, 759, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (490, '2025-08-04 09:19:11', 1, 2, 7.00, 0.90, 0.80, NULL, 114, 292, 3978, 0.10, 26.5, 767, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (491, '2025-08-03 09:25:11', 1, 2, 7.20, 2.90, 1.00, NULL, 80, 369, 3215, 0.10, 28.5, 758, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (492, '2025-08-02 11:16:11', 1, 2, 7.10, 2.40, 1.50, NULL, 93, 283, 3513, 0.00, 29.6, 751, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (493, '2025-08-01 11:40:11', 1, 2, 7.70, 2.20, 2.00, NULL, 94, 339, 3729, 0.90, 28.7, 742, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (494, '2025-07-31 11:39:11', 1, 2, 7.80, 2.00, 2.00, NULL, 102, 335, 3476, 0.20, 28.0, 691, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (495, '2025-07-30 08:47:11', 1, 2, 7.10, 1.00, 3.00, NULL, 111, 342, 3123, 0.10, 27.1, 785, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (496, '2025-07-29 10:02:11', 1, 2, 7.20, 0.80, 1.40, NULL, 114, 369, 3974, 0.10, 28.2, 795, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (497, '2025-07-28 08:51:11', 1, 2, 7.00, 1.30, 1.30, NULL, 114, 372, 3759, 0.80, 28.4, 668, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (498, '2025-07-27 10:28:11', 1, 2, 7.20, 1.40, 2.10, NULL, 97, 316, 3857, 1.00, 27.8, 777, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (499, '2025-07-26 09:33:11', 1, 2, 7.10, 2.40, 3.10, NULL, 95, 370, 3457, 0.90, 29.4, 677, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (500, '2025-07-25 11:13:11', 1, 2, 7.10, 2.50, 2.60, NULL, 92, 279, 3758, 0.30, 29.4, 659, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (501, '2025-07-24 10:56:11', 1, 2, 7.80, 1.00, 1.00, NULL, 114, 200, 3179, 0.20, 26.8, 694, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (502, '2025-07-23 08:11:11', 1, 2, 7.40, 1.70, 3.40, NULL, 95, 368, 3720, 0.60, 26.4, 748, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (503, '2025-07-22 09:19:11', 1, 2, 7.10, 0.90, 2.70, NULL, 113, 200, 3748, 0.40, 26.0, 783, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (504, '2025-07-21 10:37:11', 1, 2, 7.60, 0.50, 1.50, NULL, 101, 272, 3850, 0.40, 28.7, 727, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (505, '2025-07-20 11:45:11', 1, 2, 7.20, 2.90, 2.40, NULL, 92, 293, 3148, 0.50, 27.5, 712, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (506, '2025-07-19 11:15:11', 1, 2, 7.60, 2.30, 1.10, NULL, 80, 254, 3421, 0.30, 26.2, 691, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (507, '2025-07-18 08:38:11', 1, 2, 7.70, 1.70, 1.10, NULL, 83, 346, 3354, 0.00, 26.0, 704, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (508, '2025-07-17 09:24:11', 1, 2, 7.50, 1.60, 2.10, NULL, 81, 262, 3574, 0.70, 27.5, 774, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (509, '2025-07-16 08:13:11', 1, 2, 7.70, 1.90, 0.80, NULL, 109, 255, 3938, 0.20, 28.3, 665, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (510, '2025-07-15 09:36:11', 1, 2, 7.40, 2.40, 2.30, NULL, 120, 365, 3356, 0.70, 28.1, 735, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (511, '2025-07-14 10:37:11', 1, 2, 7.50, 0.90, 0.80, NULL, 83, 398, 3319, 0.80, 26.9, 693, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (512, '2025-07-13 11:50:11', 1, 2, 7.40, 1.80, 2.90, NULL, 91, 312, 3993, 0.40, 29.6, 714, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (513, '2025-07-12 11:53:11', 1, 2, 7.80, 1.50, 3.00, NULL, 91, 400, 3145, 0.40, 26.1, 652, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (514, '2025-07-11 08:12:11', 1, 2, 7.60, 1.90, 1.00, NULL, 107, 331, 3814, 0.40, 26.2, 784, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (515, '2025-07-10 09:14:11', 1, 2, 7.60, 2.90, 2.00, NULL, 115, 239, 3581, 0.60, 26.0, 671, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (516, '2025-07-09 09:38:11', 1, 2, 7.40, 1.50, 2.60, NULL, 92, 341, 3618, 0.20, 29.0, 759, 'Routine monthly check', '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (517, '2025-07-08 11:52:11', 1, 2, 7.70, 2.70, 1.20, NULL, 119, 390, 3567, 0.10, 26.2, 775, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (518, '2025-07-07 10:10:11', 1, 2, 7.10, 1.90, 3.20, NULL, 85, 368, 3082, 0.80, 27.6, 734, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (519, '2025-07-06 10:26:11', 1, 2, 7.30, 2.50, 2.70, NULL, 98, 327, 3012, 0.30, 29.3, 769, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (520, '2025-07-05 09:38:11', 1, 2, 7.60, 1.60, 0.80, NULL, 98, 300, 3533, 0.70, 27.3, 718, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (521, '2025-07-04 10:08:11', 1, 2, 7.40, 1.20, 1.80, NULL, 89, 294, 3184, 0.70, 26.0, 753, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (522, '2025-07-03 10:33:11', 1, 2, 7.60, 1.40, 2.30, NULL, 118, 360, 3106, 1.00, 28.4, 677, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (523, '2025-07-02 10:33:11', 1, 2, 7.60, 2.60, 2.80, NULL, 108, 310, 3933, 1.00, 26.0, 655, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (524, '2025-07-01 09:42:11', 1, 2, 7.30, 2.30, 3.30, NULL, 94, 354, 3991, 0.20, 28.3, 785, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (525, '2025-06-30 10:29:11', 1, 2, 7.40, 1.80, 3.20, NULL, 106, 253, 3282, 0.40, 28.3, 677, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (526, '2025-06-29 09:12:11', 1, 2, 7.70, 2.20, 2.40, NULL, 112, 360, 3232, 0.50, 28.3, 695, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (527, '2025-06-28 09:07:11', 1, 2, 7.30, 2.70, 2.50, NULL, 100, 339, 3219, 0.40, 28.8, 742, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (528, '2025-06-27 09:07:11', 1, 2, 7.80, 2.40, 2.90, NULL, 97, 261, 3420, 0.80, 26.6, 656, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (529, '2025-06-26 10:34:11', 1, 2, 7.60, 1.50, 0.70, NULL, 115, 375, 3126, 0.60, 27.6, 667, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (530, '2025-06-25 10:04:11', 1, 2, 7.70, 2.80, 3.00, NULL, 115, 212, 3811, 0.50, 30.0, 745, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (531, '2025-06-24 10:04:11', 1, 2, 7.30, 2.90, 3.00, NULL, 88, 225, 3964, 0.90, 26.1, 758, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (532, '2025-06-23 09:55:11', 1, 2, 7.50, 1.70, 0.50, NULL, 101, 346, 3114, 0.60, 28.4, 668, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (533, '2025-06-22 08:25:11', 1, 2, 7.30, 2.80, 2.70, NULL, 114, 277, 3781, 0.10, 28.7, 660, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (534, '2025-06-21 11:15:11', 1, 2, 7.40, 0.90, 2.70, NULL, 85, 338, 3413, 1.00, 27.0, 765, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (535, '2025-06-20 10:42:11', 1, 2, 7.10, 0.60, 0.60, NULL, 120, 310, 3822, 1.00, 26.5, 716, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (536, '2025-06-19 10:23:11', 1, 2, 7.10, 2.30, 0.70, NULL, 120, 351, 3770, 0.30, 29.9, 726, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (537, '2025-06-18 11:24:11', 1, 2, 7.10, 2.20, 0.80, NULL, 80, 276, 3189, 0.80, 29.6, 704, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (538, '2025-06-17 08:54:11', 1, 2, 7.10, 3.00, 0.70, NULL, 112, 302, 3492, 0.90, 29.7, 763, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (539, '2025-06-16 11:29:11', 1, 2, 7.10, 1.00, 2.70, NULL, 110, 356, 3795, 0.20, 30.0, 716, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (540, '2025-06-15 09:41:11', 1, 2, 7.70, 1.10, 2.40, NULL, 93, 307, 3695, 0.20, 29.2, 761, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (541, '2025-06-14 10:19:11', 1, 2, 7.30, 1.00, 1.20, NULL, 103, 280, 3356, 0.50, 29.1, 666, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (542, '2025-06-13 09:51:11', 1, 2, 7.50, 0.70, 3.00, NULL, 94, 305, 3723, 0.60, 29.6, 741, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (543, '2025-06-12 08:24:11', 1, 2, 7.80, 1.30, 2.80, NULL, 112, 364, 3539, 0.20, 28.5, 676, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (544, '2025-06-11 09:08:11', 1, 2, 7.30, 0.70, 2.30, NULL, 90, 257, 3467, 1.00, 29.1, 679, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (545, '2025-06-10 11:13:11', 1, 2, 7.60, 2.80, 1.50, NULL, 118, 344, 3185, 0.60, 28.9, 761, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (546, '2025-06-09 08:37:11', 1, 2, 7.60, 1.90, 3.00, NULL, 83, 241, 3702, 0.10, 29.7, 708, 'Routine monthly check', '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (547, '2025-06-08 08:34:11', 1, 2, 7.10, 0.70, 2.00, NULL, 92, 275, 3717, 1.00, 26.0, 786, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (548, '2025-06-07 09:23:11', 1, 2, 7.20, 0.70, 0.90, NULL, 89, 242, 3267, 0.60, 26.9, 725, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (549, '2025-06-06 08:27:11', 1, 2, 7.50, 0.70, 0.80, NULL, 109, 204, 3803, 0.40, 29.0, 664, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (550, '2025-06-05 09:03:11', 1, 2, 7.60, 2.50, 1.10, NULL, 115, 256, 3142, 0.90, 28.1, 780, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (551, '2025-06-04 11:52:11', 1, 2, 7.80, 2.20, 1.10, NULL, 84, 252, 3327, 0.00, 27.3, 743, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (552, '2025-06-03 08:41:11', 1, 2, 7.10, 1.20, 2.90, NULL, 119, 362, 3540, 0.60, 26.2, 716, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (553, '2025-06-02 08:19:11', 1, 2, 7.70, 1.10, 3.00, NULL, 115, 263, 3380, 0.70, 26.4, 795, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (554, '2025-06-01 11:55:11', 1, 2, 7.70, 1.00, 3.50, NULL, 91, 219, 3605, 0.10, 28.0, 694, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (555, '2025-05-31 11:22:11', 1, 2, 7.50, 1.90, 2.50, NULL, 115, 334, 3931, 0.30, 27.0, 687, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (556, '2025-05-30 10:55:11', 1, 2, 7.20, 2.50, 3.00, NULL, 100, 263, 3755, 0.70, 28.4, 669, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (557, '2025-05-29 11:19:11', 1, 2, 7.50, 1.30, 3.40, NULL, 90, 380, 3276, 0.70, 27.5, 701, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (558, '2025-05-28 11:06:11', 1, 2, 7.20, 2.50, 1.10, NULL, 108, 201, 3470, 0.80, 28.5, 713, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (559, '2025-05-27 09:52:11', 1, 2, 7.00, 2.40, 0.90, NULL, 82, 368, 3859, 0.80, 29.7, 770, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (560, '2025-05-26 10:28:11', 1, 2, 7.30, 2.00, 1.00, NULL, 94, 221, 3542, 0.40, 28.5, 677, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (561, '2025-05-25 11:51:11', 1, 2, 7.60, 1.90, 2.90, NULL, 93, 207, 3555, 0.50, 28.5, 688, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (562, '2025-05-24 11:02:11', 1, 2, 7.10, 2.50, 1.20, NULL, 113, 260, 3678, 0.50, 29.3, 673, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (563, '2025-05-23 11:14:11', 1, 2, 7.60, 2.70, 1.90, NULL, 106, 371, 3429, 0.00, 28.1, 749, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (564, '2025-05-22 09:07:11', 1, 2, 7.10, 1.40, 2.70, NULL, 114, 308, 3577, 1.00, 27.5, 670, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (565, '2025-05-21 09:31:11', 1, 2, 7.20, 1.80, 2.40, NULL, 115, 265, 3388, 0.30, 26.3, 658, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (566, '2025-05-20 08:44:11', 1, 2, 7.50, 2.80, 3.10, NULL, 88, 257, 3103, 0.20, 26.8, 779, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (567, '2025-05-19 11:59:11', 1, 2, 7.00, 1.80, 2.40, NULL, 119, 207, 3548, 0.90, 29.7, 700, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (568, '2025-05-18 08:56:11', 1, 2, 7.20, 0.50, 1.20, NULL, 95, 241, 3378, 0.50, 27.2, 739, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (569, '2025-05-17 08:32:11', 1, 2, 7.80, 0.80, 2.30, NULL, 84, 327, 3720, 0.30, 26.3, 714, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (570, '2025-05-16 11:01:11', 1, 2, 7.20, 0.70, 1.60, NULL, 98, 375, 3601, 0.30, 28.6, 776, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (571, '2025-05-15 11:31:11', 1, 2, 7.40, 1.00, 1.40, NULL, 93, 279, 3506, 0.60, 29.1, 714, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (572, '2025-05-14 10:38:11', 1, 2, 7.50, 2.70, 1.30, NULL, 82, 206, 3399, 0.90, 26.7, 666, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (573, '2025-05-13 08:34:11', 1, 2, 7.40, 1.10, 1.60, NULL, 113, 382, 3641, 0.60, 27.0, 650, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (574, '2025-05-12 09:05:11', 1, 2, 7.70, 1.10, 3.20, NULL, 96, 367, 3610, 0.00, 28.8, 711, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (575, '2025-05-11 08:06:11', 1, 2, 7.70, 0.90, 2.10, NULL, 109, 252, 3163, 0.50, 28.1, 797, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (576, '2025-05-10 09:30:11', 1, 2, 7.20, 0.70, 1.30, NULL, 87, 339, 3462, 0.00, 29.7, 745, 'Routine monthly check', '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (577, '2025-05-09 11:01:11', 1, 2, 7.60, 2.50, 2.00, NULL, 103, 397, 3521, 0.60, 26.8, 784, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (578, '2025-05-08 08:34:11', 1, 2, 7.00, 0.90, 1.00, NULL, 119, 290, 3416, 0.00, 28.0, 683, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (579, '2025-05-07 08:32:11', 1, 2, 7.80, 2.80, 2.80, NULL, 93, 331, 3774, 0.50, 27.5, 734, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (580, '2025-05-06 10:00:11', 1, 2, 7.40, 1.40, 0.90, NULL, 114, 301, 3834, 0.10, 29.2, 785, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (581, '2025-05-05 10:48:11', 1, 2, 7.40, 1.00, 1.60, NULL, 117, 318, 3968, 1.00, 29.0, 783, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (582, '2025-05-04 08:37:11', 1, 2, 7.50, 0.70, 3.40, NULL, 91, 296, 3477, 0.10, 28.1, 798, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (583, '2025-05-03 09:20:11', 1, 2, 7.80, 1.20, 1.80, NULL, 89, 264, 3096, 0.10, 27.2, 798, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (584, '2025-05-02 09:10:11', 1, 2, 7.70, 1.10, 2.40, NULL, 97, 228, 3703, 0.20, 29.0, 657, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (585, '2025-05-01 11:26:11', 1, 2, 7.00, 2.30, 2.90, NULL, 107, 344, 3511, 0.20, 28.4, 664, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (586, '2025-04-30 08:05:11', 1, 2, 7.70, 2.40, 1.40, NULL, 93, 285, 3250, 0.60, 28.1, 800, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (587, '2025-04-29 10:30:11', 1, 2, 7.10, 1.20, 2.70, NULL, 87, 378, 3208, 0.50, 26.9, 792, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (588, '2025-04-28 09:20:11', 1, 2, 7.10, 1.50, 2.40, NULL, 115, 263, 3582, 0.10, 26.1, 716, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (589, '2025-04-27 10:21:11', 1, 2, 7.60, 2.40, 2.50, NULL, 89, 341, 3400, 0.90, 29.2, 714, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (590, '2025-04-26 09:33:11', 1, 2, 7.10, 2.00, 2.70, NULL, 114, 294, 3058, 1.00, 29.6, 784, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (591, '2025-04-25 09:30:11', 1, 2, 7.50, 0.90, 2.50, NULL, 115, 327, 3318, 0.60, 28.2, 704, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (592, '2025-04-24 10:55:11', 1, 2, 7.40, 0.80, 1.10, NULL, 93, 387, 3238, 0.70, 29.9, 679, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (593, '2025-04-23 09:02:11', 1, 2, 7.00, 2.70, 1.50, NULL, 99, 320, 3742, 0.30, 29.7, 706, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (594, '2025-04-22 09:20:11', 1, 2, 7.20, 1.80, 2.00, NULL, 81, 335, 3128, 0.90, 27.5, 679, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (595, '2025-04-21 10:35:11', 1, 2, 7.00, 1.30, 3.10, NULL, 117, 302, 3777, 0.40, 27.8, 757, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (596, '2025-04-20 09:11:11', 1, 2, 7.10, 1.60, 0.90, NULL, 119, 351, 3271, 0.30, 27.2, 709, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (597, '2025-04-19 08:18:11', 1, 2, 7.80, 1.10, 0.60, NULL, 110, 305, 3788, 1.00, 27.0, 747, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (598, '2025-04-18 11:49:11', 1, 2, 7.30, 2.10, 2.80, NULL, 108, 216, 3596, 0.50, 26.4, 687, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (599, '2025-04-17 08:46:11', 1, 2, 7.60, 2.00, 0.90, NULL, 100, 389, 3945, 0.90, 29.6, 786, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (600, '2025-04-16 09:39:11', 1, 2, 7.00, 1.70, 2.50, NULL, 88, 301, 3418, 0.20, 27.7, 777, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (601, '2025-04-15 10:39:11', 1, 2, 7.20, 1.30, 2.20, NULL, 108, 291, 3984, 0.70, 26.0, 684, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (602, '2025-04-14 11:01:11', 1, 2, 7.40, 0.60, 1.80, NULL, 104, 354, 3747, 0.00, 28.0, 779, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (603, '2025-04-13 09:40:11', 1, 2, 7.50, 2.70, 1.70, NULL, 83, 285, 3687, 0.50, 29.9, 749, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (604, '2025-04-12 09:36:11', 1, 2, 7.60, 2.80, 3.40, NULL, 106, 352, 3744, 0.00, 27.7, 766, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (605, '2025-04-11 09:26:11', 1, 2, 7.10, 0.80, 3.30, NULL, 113, 343, 3928, 0.80, 28.1, 773, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (606, '2025-04-10 08:39:11', 1, 2, 7.30, 0.90, 2.70, NULL, 97, 214, 3191, 0.40, 29.2, 687, 'Routine monthly check', '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (607, '2025-04-09 08:50:11', 1, 2, 7.30, 2.10, 2.00, NULL, 119, 203, 3265, 0.90, 28.7, 735, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (608, '2025-04-08 09:05:11', 1, 2, 7.10, 2.90, 2.90, NULL, 89, 204, 3284, 0.90, 26.2, 676, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (609, '2025-04-07 11:27:11', 1, 2, 7.20, 1.20, 1.30, NULL, 87, 283, 3261, 0.70, 26.5, 773, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (610, '2025-04-06 10:32:11', 1, 2, 7.00, 2.50, 1.60, NULL, 89, 220, 3500, 0.50, 29.0, 717, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (611, '2025-04-05 10:48:11', 1, 2, 7.50, 2.30, 1.20, NULL, 115, 299, 3985, 0.60, 26.5, 778, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (612, '2025-04-04 08:12:11', 1, 2, 7.20, 1.90, 0.80, NULL, 89, 296, 3955, 0.70, 29.0, 692, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (613, '2025-04-03 09:28:11', 1, 2, 7.50, 1.10, 2.70, NULL, 102, 397, 3206, 0.50, 28.7, 683, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (614, '2025-04-02 08:17:11', 1, 2, 7.50, 2.30, 1.30, NULL, 112, 359, 3344, 0.70, 29.7, 693, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (615, '2025-04-01 10:01:11', 1, 2, 7.50, 0.60, 3.00, NULL, 91, 269, 3962, 0.00, 27.2, 706, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (616, '2025-03-31 11:15:11', 1, 2, 7.10, 1.40, 0.90, NULL, 116, 399, 3883, 0.60, 28.0, 726, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (617, '2025-03-30 10:43:11', 1, 2, 7.70, 2.80, 3.30, NULL, 87, 342, 3796, 0.70, 28.5, 772, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (618, '2025-03-29 08:19:11', 1, 2, 7.60, 2.60, 3.20, NULL, 115, 319, 3578, 0.70, 29.8, 693, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (619, '2025-03-28 08:22:11', 1, 2, 7.70, 1.20, 2.40, NULL, 84, 317, 3983, 1.00, 28.0, 665, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (620, '2025-03-27 11:59:11', 1, 2, 7.60, 2.30, 3.20, NULL, 97, 373, 3172, 0.30, 26.6, 792, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (621, '2025-03-26 09:25:11', 1, 2, 7.80, 2.30, 2.70, NULL, 95, 318, 3203, 0.70, 28.5, 800, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (622, '2025-03-25 09:44:11', 1, 2, 7.80, 2.70, 1.90, NULL, 86, 319, 3348, 0.90, 30.0, 661, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (623, '2025-03-24 10:51:11', 1, 2, 7.50, 1.70, 0.50, NULL, 119, 302, 3259, 0.20, 27.2, 671, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (624, '2025-03-23 10:03:11', 1, 2, 7.10, 1.30, 1.20, NULL, 99, 221, 3412, 0.90, 26.5, 657, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (625, '2025-03-22 10:40:11', 1, 2, 7.20, 3.00, 0.90, NULL, 95, 370, 3836, 0.40, 29.2, 666, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (626, '2025-03-21 09:03:11', 1, 2, 7.00, 0.70, 0.80, NULL, 82, 374, 3615, 0.00, 29.7, 742, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (627, '2025-03-20 08:58:11', 1, 2, 7.50, 1.30, 1.40, NULL, 94, 270, 3229, 0.60, 26.9, 745, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (628, '2025-03-19 08:41:11', 1, 2, 7.10, 1.20, 2.50, NULL, 102, 266, 3766, 0.00, 26.1, 775, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (629, '2025-03-18 09:21:11', 1, 2, 7.30, 1.60, 3.00, NULL, 85, 333, 3543, 0.60, 29.9, 681, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (630, '2025-03-17 10:19:11', 1, 2, 7.80, 1.60, 3.30, NULL, 90, 274, 3077, 0.80, 29.5, 784, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (631, '2025-03-16 10:39:11', 1, 2, 7.40, 2.20, 1.90, NULL, 112, 312, 3040, 0.10, 29.3, 656, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (632, '2025-03-15 11:44:11', 1, 2, 7.80, 1.90, 2.30, NULL, 113, 204, 3434, 0.50, 26.3, 698, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (633, '2025-03-14 08:14:11', 1, 2, 7.50, 1.90, 0.60, NULL, 116, 386, 3811, 1.00, 28.3, 775, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (634, '2025-03-13 11:04:11', 1, 2, 7.20, 0.70, 1.10, NULL, 117, 368, 3433, 0.50, 30.0, 742, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (635, '2025-03-12 09:10:11', 1, 2, 7.30, 2.30, 2.30, NULL, 81, 397, 3214, 0.90, 27.2, 720, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (636, '2025-03-11 11:41:11', 1, 2, 7.00, 1.60, 2.30, NULL, 80, 278, 3666, 0.40, 29.1, 741, 'Routine monthly check', '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (637, '2025-03-10 08:32:11', 1, 2, 7.20, 2.50, 1.10, NULL, 97, 309, 3886, 0.90, 30.0, 712, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (638, '2025-03-09 09:01:11', 1, 2, 7.70, 0.70, 1.50, NULL, 112, 356, 3261, 0.70, 29.6, 651, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (639, '2025-03-08 10:25:11', 1, 2, 7.40, 1.90, 1.90, NULL, 90, 399, 3616, 1.00, 26.6, 779, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (640, '2025-03-07 09:20:11', 1, 2, 7.60, 2.10, 2.00, NULL, 114, 239, 3541, 0.60, 28.7, 654, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (641, '2025-03-06 08:18:11', 1, 2, 7.80, 2.10, 1.30, NULL, 104, 381, 3453, 0.10, 28.1, 659, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (642, '2025-03-05 09:48:11', 1, 2, 7.50, 0.90, 3.00, NULL, 97, 383, 3249, 0.00, 27.7, 797, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (643, '2025-03-04 09:52:11', 1, 2, 7.30, 0.80, 2.40, NULL, 85, 272, 3984, 0.40, 29.9, 776, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (644, '2025-03-03 09:56:11', 1, 2, 7.30, 1.10, 2.50, NULL, 110, 380, 3010, 0.30, 26.1, 671, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (645, '2025-03-02 10:11:11', 1, 2, 7.30, 2.50, 2.70, NULL, 95, 288, 3835, 0.30, 27.3, 743, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (646, '2025-03-01 10:50:11', 1, 2, 7.40, 1.60, 2.30, NULL, 101, 284, 3476, 0.60, 27.0, 749, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (647, '2025-02-28 08:27:11', 1, 2, 7.50, 2.10, 2.50, NULL, 88, 246, 3242, 0.10, 28.2, 795, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (648, '2025-02-27 11:40:11', 1, 2, 7.30, 0.60, 2.60, NULL, 98, 379, 3565, 0.30, 28.9, 662, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (649, '2025-02-26 10:07:11', 1, 2, 7.30, 2.10, 2.70, NULL, 89, 335, 3642, 0.20, 28.4, 730, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (650, '2025-02-25 08:48:11', 1, 2, 7.60, 1.70, 1.00, NULL, 104, 320, 3808, 0.00, 29.1, 751, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (651, '2025-02-24 10:52:11', 1, 2, 7.30, 2.10, 3.50, NULL, 93, 205, 3615, 0.40, 26.6, 733, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (652, '2025-02-23 09:44:11', 1, 2, 7.50, 1.70, 1.90, NULL, 106, 244, 3218, 0.30, 27.0, 686, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (653, '2025-02-22 08:12:11', 1, 2, 7.70, 2.60, 1.40, NULL, 87, 280, 3886, 0.20, 28.8, 766, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (654, '2025-02-21 11:57:11', 1, 2, 7.40, 2.30, 3.50, NULL, 80, 292, 3169, 0.90, 29.5, 668, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (655, '2025-02-20 09:45:11', 1, 2, 7.70, 2.90, 1.50, NULL, 112, 337, 3916, 0.70, 27.0, 698, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (656, '2025-02-19 11:03:11', 1, 2, 7.80, 2.40, 2.30, NULL, 91, 375, 3525, 0.80, 27.1, 711, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (657, '2025-02-18 11:55:11', 1, 2, 7.80, 2.20, 2.60, NULL, 91, 308, 3350, 0.80, 29.1, 700, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (658, '2025-02-17 10:29:11', 1, 2, 7.60, 2.00, 1.20, NULL, 97, 397, 3763, 0.70, 27.2, 688, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (659, '2025-02-16 11:27:11', 1, 2, 7.80, 2.50, 0.50, NULL, 115, 300, 3377, 0.30, 26.5, 686, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (660, '2025-02-15 08:17:11', 1, 2, 7.40, 3.00, 0.90, NULL, 114, 361, 3859, 0.10, 29.8, 714, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (661, '2025-02-14 08:59:11', 1, 2, 7.60, 0.60, 1.40, NULL, 119, 223, 3302, 0.70, 29.3, 666, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (662, '2025-02-13 11:20:11', 1, 2, 7.70, 1.90, 1.80, NULL, 91, 379, 3462, 0.30, 30.0, 791, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (663, '2025-02-12 10:25:11', 1, 2, 7.30, 2.00, 0.60, NULL, 92, 276, 3297, 0.10, 27.2, 754, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (664, '2025-02-11 09:27:11', 1, 2, 7.20, 0.80, 1.60, NULL, 94, 266, 3786, 0.10, 28.2, 798, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (665, '2025-02-10 09:43:11', 1, 2, 7.40, 2.30, 2.40, NULL, 87, 248, 3460, 0.00, 26.4, 737, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (666, '2025-02-09 09:39:11', 1, 2, 7.40, 2.30, 1.70, NULL, 93, 243, 3556, 0.20, 27.5, 754, 'Routine monthly check', '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (667, '2025-02-08 09:33:11', 1, 2, 7.80, 2.50, 1.00, NULL, 109, 393, 3680, 0.50, 26.0, 752, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (668, '2025-02-07 08:54:11', 1, 2, 7.80, 1.40, 1.80, NULL, 96, 273, 3057, 0.50, 26.5, 770, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (669, '2025-02-06 10:26:11', 1, 2, 7.20, 2.40, 2.70, NULL, 85, 266, 3122, 0.10, 29.7, 698, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (670, '2025-02-05 08:59:11', 1, 2, 7.10, 1.30, 3.00, NULL, 111, 248, 3501, 0.40, 28.2, 704, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (671, '2025-02-04 09:44:11', 1, 2, 7.30, 2.40, 0.70, NULL, 94, 288, 3438, 0.20, 29.4, 786, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (672, '2025-02-03 11:46:11', 1, 2, 7.10, 2.10, 2.60, NULL, 91, 382, 3889, 1.00, 28.3, 780, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (673, '2025-02-02 08:22:11', 1, 2, 7.70, 1.50, 2.90, NULL, 98, 392, 3403, 0.50, 28.5, 729, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (674, '2025-02-01 10:27:11', 1, 2, 7.20, 0.90, 0.70, NULL, 93, 376, 3714, 0.70, 27.7, 680, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (675, '2025-01-31 10:25:11', 1, 2, 7.60, 0.90, 1.60, NULL, 81, 334, 3039, 0.40, 29.4, 701, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (676, '2025-01-30 09:00:11', 1, 2, 7.50, 2.60, 1.00, NULL, 112, 381, 3384, 0.00, 28.1, 718, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (677, '2025-01-29 09:02:11', 1, 2, 7.10, 2.50, 1.00, NULL, 106, 214, 3299, 0.30, 28.4, 703, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (678, '2025-01-28 10:57:11', 1, 2, 7.60, 0.50, 0.50, NULL, 114, 338, 3138, 0.10, 28.5, 677, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (679, '2025-01-27 09:29:11', 1, 2, 7.10, 2.30, 1.50, NULL, 102, 400, 3695, 0.50, 29.6, 682, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (680, '2025-01-26 11:49:11', 1, 2, 7.10, 2.60, 1.10, NULL, 96, 244, 3198, 0.30, 26.6, 725, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (681, '2025-01-25 08:14:11', 1, 2, 7.30, 2.50, 1.60, NULL, 85, 308, 3578, 0.10, 29.0, 750, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (682, '2025-01-24 11:35:11', 1, 2, 7.40, 2.60, 1.70, NULL, 103, 322, 3724, 1.00, 27.9, 738, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (683, '2025-01-23 11:13:11', 1, 2, 7.50, 1.90, 2.40, NULL, 115, 228, 3161, 0.60, 28.3, 783, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (684, '2025-01-22 10:20:11', 1, 2, 7.70, 1.20, 2.80, NULL, 114, 275, 3743, 0.10, 26.4, 689, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (685, '2025-01-21 10:06:11', 1, 2, 7.20, 1.30, 0.60, NULL, 112, 210, 3303, 0.20, 26.8, 686, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (686, '2025-01-20 08:29:11', 1, 2, 7.60, 1.30, 0.70, NULL, 88, 207, 3608, 0.20, 29.1, 796, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (687, '2025-01-19 10:57:11', 1, 2, 7.70, 2.40, 0.70, NULL, 96, 205, 3673, 0.50, 29.4, 765, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (688, '2025-01-18 09:46:11', 1, 2, 7.70, 1.70, 2.80, NULL, 82, 257, 3495, 0.00, 26.5, 657, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (689, '2025-01-17 10:13:11', 1, 2, 7.10, 1.60, 1.90, NULL, 108, 330, 3221, 0.30, 28.8, 710, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (690, '2025-01-16 10:57:11', 1, 2, 7.80, 1.70, 3.10, NULL, 114, 378, 3417, 0.90, 28.0, 722, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (691, '2025-01-15 11:53:11', 1, 2, 7.20, 1.30, 2.70, NULL, 92, 301, 3498, 0.70, 29.9, 684, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (692, '2025-01-14 09:28:11', 1, 2, 7.30, 2.50, 1.60, NULL, 106, 364, 3613, 0.90, 26.1, 787, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (693, '2025-01-13 11:38:11', 1, 2, 7.80, 1.20, 1.50, NULL, 104, 272, 3912, 0.40, 27.5, 700, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (694, '2025-01-12 09:06:11', 1, 2, 7.10, 1.60, 3.10, NULL, 97, 226, 3423, 0.80, 27.8, 700, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (695, '2025-01-11 10:34:11', 1, 2, 7.70, 1.80, 0.70, NULL, 112, 347, 3563, 0.50, 28.6, 670, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (696, '2025-01-10 11:45:11', 1, 2, 7.30, 2.40, 1.50, NULL, 84, 394, 3045, 0.20, 27.0, 729, 'Routine monthly check', '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (697, '2025-01-09 08:13:11', 1, 2, 7.30, 2.60, 1.20, NULL, 105, 228, 3267, 0.20, 27.0, 661, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (698, '2025-01-08 11:55:11', 1, 2, 7.30, 0.70, 2.30, NULL, 111, 369, 3231, 1.00, 27.3, 719, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (699, '2025-01-07 11:04:11', 1, 2, 7.70, 2.20, 1.00, NULL, 93, 353, 3724, 0.90, 28.7, 748, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (700, '2025-01-06 11:56:11', 1, 2, 7.60, 0.70, 1.10, NULL, 116, 226, 3840, 0.70, 27.0, 689, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (701, '2025-01-05 08:32:11', 1, 2, 7.20, 2.30, 1.10, NULL, 80, 397, 3341, 0.40, 29.7, 753, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (702, '2025-01-04 09:05:11', 1, 2, 7.30, 1.10, 3.50, NULL, 97, 263, 3563, 0.50, 26.7, 760, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (703, '2025-01-03 08:28:11', 1, 2, 7.70, 1.80, 0.70, NULL, 118, 264, 3885, 0.80, 29.7, 687, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (704, '2025-01-02 10:19:11', 1, 2, 7.30, 2.70, 2.90, NULL, 97, 351, 3876, 1.00, 26.1, 716, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (705, '2025-01-01 09:30:11', 1, 2, 7.30, 2.90, 2.00, NULL, 80, 362, 3087, 0.70, 28.0, 652, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (706, '2024-12-31 10:48:11', 1, 2, 7.80, 0.60, 1.00, NULL, 112, 375, 3683, 0.70, 29.7, 743, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (707, '2024-12-30 09:47:11', 1, 2, 7.70, 1.30, 2.40, NULL, 92, 238, 3984, 0.50, 29.5, 662, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (708, '2024-12-29 08:03:11', 1, 2, 7.20, 1.50, 0.60, NULL, 90, 266, 3389, 0.10, 28.8, 678, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (709, '2024-12-28 11:11:11', 1, 2, 7.20, 2.50, 1.80, NULL, 89, 302, 3837, 0.40, 27.8, 702, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (710, '2024-12-27 09:44:11', 1, 2, 7.30, 2.10, 2.00, NULL, 100, 239, 3118, 0.60, 26.1, 786, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (711, '2024-12-26 08:07:11', 1, 2, 7.20, 2.40, 2.60, NULL, 88, 360, 3603, 0.80, 27.3, 798, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (712, '2024-12-25 11:25:11', 1, 2, 7.20, 1.00, 3.00, NULL, 102, 258, 3781, 0.90, 28.1, 680, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (713, '2024-12-24 08:08:11', 1, 2, 7.00, 1.80, 1.50, NULL, 114, 255, 3690, 0.50, 28.2, 795, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (714, '2024-12-23 11:29:11', 1, 2, 7.20, 1.90, 2.90, NULL, 118, 270, 3734, 0.70, 28.5, 737, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (715, '2024-12-22 11:49:11', 1, 2, 7.60, 0.70, 3.10, NULL, 80, 323, 3755, 0.20, 27.7, 773, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (716, '2024-12-21 11:28:11', 1, 2, 7.00, 0.60, 1.00, NULL, 115, 230, 3950, 0.20, 28.2, 769, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (717, '2024-12-20 11:14:11', 1, 2, 7.60, 2.50, 0.90, NULL, 102, 330, 3855, 0.30, 29.2, 677, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (718, '2024-12-19 09:15:11', 1, 2, 7.20, 1.00, 0.90, NULL, 99, 246, 3026, 0.40, 28.9, 745, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (719, '2024-12-18 09:49:11', 1, 2, 7.00, 2.60, 2.90, NULL, 86, 238, 3077, 0.40, 28.2, 659, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (720, '2024-12-17 10:09:11', 1, 2, 7.30, 1.50, 0.50, NULL, 96, 258, 3265, 0.80, 29.3, 723, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (721, '2024-12-16 08:04:11', 1, 2, 7.10, 2.70, 1.80, NULL, 108, 292, 3290, 0.90, 28.6, 685, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (722, '2024-12-15 11:57:11', 1, 2, 7.80, 1.80, 3.30, NULL, 103, 284, 3982, 0.20, 28.7, 691, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (723, '2024-12-14 08:21:11', 1, 2, 7.00, 1.90, 1.20, NULL, 100, 354, 3531, 0.20, 27.0, 687, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (724, '2024-12-13 09:21:11', 1, 2, 7.10, 3.00, 1.10, NULL, 106, 398, 3949, 0.00, 29.6, 756, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (725, '2024-12-12 09:43:11', 1, 2, 7.20, 1.30, 0.50, NULL, 104, 218, 3773, 0.60, 27.5, 751, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (726, '2024-12-11 10:16:11', 1, 2, 7.60, 0.60, 2.80, NULL, 100, 325, 3381, 0.50, 28.8, 771, 'Routine monthly check', '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (727, '2024-12-10 08:26:11', 1, 2, 7.50, 1.60, 0.50, NULL, 105, 245, 3847, 0.60, 29.1, 694, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (728, '2024-12-09 10:04:11', 1, 2, 7.00, 1.80, 2.40, NULL, 106, 346, 3804, 1.00, 29.1, 793, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (729, '2024-12-08 08:01:11', 1, 2, 7.00, 2.80, 2.00, NULL, 118, 283, 3496, 0.20, 29.2, 790, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (730, '2024-12-07 08:36:11', 1, 2, 7.00, 1.70, 1.00, NULL, 97, 295, 3880, 0.30, 26.1, 785, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (731, '2025-12-06 10:20:11', 1, 3, 7.10, 1.30, 1.30, NULL, 105, 289, 3535, 0.50, 28.1, 758, 'Routine monthly check', '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (732, '2025-12-05 08:57:11', 1, 3, 7.20, 0.90, 2.80, NULL, 87, 231, 3539, 0.20, 26.8, 754, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (733, '2025-12-04 11:10:11', 1, 3, 7.30, 0.70, 0.80, NULL, 91, 301, 3948, 0.00, 29.9, 734, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (734, '2025-12-03 11:22:11', 1, 3, 7.00, 2.80, 1.20, NULL, 89, 367, 3430, 0.00, 26.9, 674, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (735, '2025-12-02 11:55:11', 1, 3, 7.80, 2.30, 1.60, NULL, 93, 368, 3124, 0.10, 29.7, 759, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (736, '2025-12-01 11:23:11', 1, 3, 7.60, 2.40, 0.60, NULL, 109, 280, 3957, 0.60, 27.8, 700, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (737, '2025-11-30 09:05:11', 1, 3, 7.00, 1.80, 3.20, NULL, 116, 371, 3727, 0.70, 27.3, 732, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (738, '2025-11-29 09:53:11', 1, 3, 7.80, 1.30, 1.00, NULL, 81, 344, 3260, 0.40, 29.4, 785, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (739, '2025-11-28 08:20:11', 1, 3, 7.30, 1.00, 0.70, NULL, 91, 300, 3072, 0.70, 28.9, 779, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (740, '2025-11-27 09:29:11', 1, 3, 7.80, 2.50, 2.80, NULL, 111, 382, 3688, 0.50, 28.7, 673, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (741, '2025-11-26 10:35:11', 1, 3, 7.70, 3.00, 3.40, NULL, 92, 311, 3925, 0.90, 27.4, 694, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (742, '2025-11-25 10:26:11', 1, 3, 7.60, 1.60, 1.30, NULL, 98, 265, 3247, 0.50, 28.9, 793, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (743, '2025-11-24 08:39:11', 1, 3, 7.40, 2.00, 0.50, NULL, 97, 212, 3237, 0.60, 26.4, 795, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (744, '2025-11-23 10:54:11', 1, 3, 7.50, 1.70, 0.50, NULL, 101, 262, 3198, 0.60, 28.1, 765, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (745, '2025-11-22 08:26:11', 1, 3, 7.20, 2.90, 2.60, NULL, 92, 217, 3607, 0.20, 27.0, 696, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (746, '2025-11-21 10:42:11', 1, 3, 7.40, 1.50, 3.30, NULL, 115, 353, 3283, 0.80, 28.8, 720, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (747, '2025-11-20 08:57:11', 1, 3, 7.40, 0.50, 1.10, NULL, 109, 358, 3817, 0.60, 28.2, 671, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (748, '2025-11-19 08:00:11', 1, 3, 7.40, 2.90, 2.00, NULL, 80, 373, 3266, 0.20, 29.2, 750, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (749, '2025-11-18 11:06:11', 1, 3, 7.60, 1.60, 0.50, NULL, 108, 334, 3731, 1.00, 28.3, 697, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (750, '2025-11-17 10:00:11', 1, 3, 7.60, 1.20, 3.20, NULL, 96, 394, 3538, 0.70, 28.6, 711, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (751, '2025-11-16 11:45:11', 1, 3, 7.20, 2.50, 1.90, NULL, 96, 313, 3592, 0.50, 26.5, 778, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (752, '2025-11-15 11:19:11', 1, 3, 7.60, 1.60, 3.50, NULL, 112, 388, 3987, 0.90, 27.7, 690, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (753, '2025-11-14 08:44:11', 1, 3, 7.80, 2.20, 1.20, NULL, 85, 277, 3924, 1.00, 27.2, 752, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (754, '2025-11-13 09:39:11', 1, 3, 7.60, 2.50, 1.00, NULL, 96, 297, 3694, 0.60, 29.5, 766, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (755, '2025-11-12 10:40:11', 1, 3, 7.40, 0.70, 1.50, NULL, 87, 244, 3684, 0.90, 27.3, 788, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (756, '2025-11-11 11:05:11', 1, 3, 7.30, 2.90, 1.90, NULL, 87, 234, 3067, 0.30, 28.0, 783, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (757, '2025-11-10 09:30:11', 1, 3, 7.50, 1.30, 2.10, NULL, 100, 233, 3426, 0.50, 27.2, 683, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (758, '2025-11-09 09:35:11', 1, 3, 7.20, 2.10, 1.10, NULL, 91, 293, 3402, 0.70, 26.6, 715, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (759, '2025-11-08 08:47:11', 1, 3, 7.30, 2.90, 1.60, NULL, 108, 209, 3661, 0.10, 29.3, 653, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (760, '2025-11-07 08:59:11', 1, 3, 7.20, 0.90, 1.40, NULL, 113, 276, 3766, 0.30, 29.4, 748, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (761, '2025-11-06 11:38:11', 1, 3, 7.50, 2.40, 1.50, NULL, 103, 233, 3110, 0.90, 27.1, 699, 'Routine monthly check', '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (762, '2025-11-05 09:07:11', 1, 3, 7.00, 2.60, 1.30, NULL, 106, 390, 3675, 0.60, 26.9, 752, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (763, '2025-11-04 10:16:11', 1, 3, 7.20, 0.80, 1.40, NULL, 118, 384, 3021, 0.10, 29.7, 677, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (764, '2025-11-03 10:18:11', 1, 3, 7.60, 1.30, 2.40, NULL, 86, 273, 3962, 0.60, 29.1, 787, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (765, '2025-11-02 11:08:11', 1, 3, 7.60, 0.90, 3.40, NULL, 116, 273, 3852, 0.40, 29.8, 722, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (766, '2025-11-01 10:39:11', 1, 3, 7.20, 2.10, 1.00, NULL, 99, 296, 3301, 1.00, 26.9, 769, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (767, '2025-10-31 08:58:11', 1, 3, 7.70, 1.40, 1.50, NULL, 83, 331, 3179, 0.60, 26.6, 675, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (768, '2025-10-30 10:25:11', 1, 3, 7.80, 3.00, 2.70, NULL, 109, 392, 3530, 1.00, 27.8, 697, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (769, '2025-10-29 09:00:11', 1, 3, 7.00, 2.60, 0.80, NULL, 108, 285, 3419, 1.00, 27.0, 783, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (770, '2025-10-28 10:12:11', 1, 3, 7.00, 2.80, 3.30, NULL, 89, 395, 3746, 0.00, 29.0, 698, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (771, '2025-10-27 11:09:11', 1, 3, 7.70, 2.10, 2.00, NULL, 115, 381, 3363, 0.00, 28.9, 787, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (772, '2025-10-26 09:56:11', 1, 3, 7.70, 1.50, 3.50, NULL, 87, 329, 3342, 0.40, 27.1, 779, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (773, '2025-10-25 09:27:11', 1, 3, 7.00, 1.80, 0.50, NULL, 101, 206, 3214, 0.90, 27.5, 650, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (774, '2025-10-24 08:04:11', 1, 3, 7.30, 2.40, 2.60, NULL, 108, 364, 3056, 0.90, 28.2, 736, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (775, '2025-10-23 08:27:11', 1, 3, 7.60, 2.00, 2.40, NULL, 93, 327, 3875, 0.90, 29.8, 737, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (776, '2025-10-22 09:42:11', 1, 3, 7.30, 2.10, 2.90, NULL, 97, 238, 3590, 0.00, 27.3, 760, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (777, '2025-10-21 11:47:11', 1, 3, 7.40, 2.60, 2.00, NULL, 95, 238, 3387, 0.50, 27.4, 708, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (778, '2025-10-20 08:11:11', 1, 3, 7.50, 2.00, 1.60, NULL, 86, 371, 3867, 1.00, 27.9, 754, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (779, '2025-10-19 10:04:11', 1, 3, 7.40, 2.10, 2.70, NULL, 103, 206, 3847, 0.80, 27.9, 761, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (780, '2025-10-18 08:02:11', 1, 3, 7.80, 1.70, 3.40, NULL, 113, 361, 3843, 0.20, 28.6, 704, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (781, '2025-10-17 10:58:11', 1, 3, 7.80, 0.90, 2.30, NULL, 100, 376, 3456, 0.20, 27.9, 754, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (782, '2025-10-16 10:45:11', 1, 3, 7.80, 3.00, 2.90, NULL, 116, 330, 3188, 0.50, 27.0, 662, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (783, '2025-10-15 09:56:11', 1, 3, 7.20, 2.00, 0.50, NULL, 84, 234, 3195, 0.60, 28.6, 743, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (784, '2025-10-14 10:54:11', 1, 3, 7.20, 2.40, 3.50, NULL, 94, 347, 3255, 0.20, 27.3, 694, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (785, '2025-10-13 08:12:11', 1, 3, 7.30, 1.00, 2.70, NULL, 106, 314, 3707, 0.90, 28.4, 754, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (786, '2025-10-12 11:54:11', 1, 3, 7.30, 2.60, 1.50, NULL, 111, 261, 3263, 0.60, 28.3, 676, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (787, '2025-10-11 09:04:11', 1, 3, 7.20, 2.40, 2.80, NULL, 88, 266, 3043, 0.30, 27.9, 771, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (788, '2025-10-10 09:36:11', 1, 3, 7.70, 1.20, 0.90, NULL, 89, 255, 3245, 0.60, 26.1, 759, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (789, '2025-10-09 11:13:11', 1, 3, 7.80, 0.90, 0.60, NULL, 105, 398, 3688, 0.40, 27.0, 708, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (790, '2025-10-08 11:18:11', 1, 3, 7.20, 0.60, 0.90, NULL, 87, 238, 3931, 0.60, 29.4, 783, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (791, '2025-10-07 11:53:11', 1, 3, 7.40, 2.10, 3.10, NULL, 80, 252, 3766, 0.50, 27.0, 671, 'Routine monthly check', '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (792, '2025-10-06 11:32:11', 1, 3, 7.10, 2.70, 2.20, NULL, 108, 289, 3567, 0.00, 27.6, 654, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (793, '2025-10-05 09:36:11', 1, 3, 7.50, 2.40, 2.00, NULL, 92, 375, 3951, 0.20, 29.8, 781, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (794, '2025-10-04 08:36:11', 1, 3, 7.80, 2.60, 1.10, NULL, 109, 346, 3312, 0.60, 29.2, 651, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (795, '2025-10-03 08:19:11', 1, 3, 7.20, 1.10, 0.60, NULL, 90, 246, 3583, 0.80, 28.1, 795, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (796, '2025-10-02 09:20:11', 1, 3, 7.40, 1.80, 2.30, NULL, 101, 251, 3546, 1.00, 28.1, 683, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (797, '2025-10-01 08:05:11', 1, 3, 7.00, 1.10, 3.50, NULL, 95, 380, 3525, 0.30, 29.1, 795, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (798, '2025-09-30 08:11:11', 1, 3, 7.40, 1.20, 1.30, NULL, 82, 285, 3071, 0.90, 30.0, 774, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (799, '2025-09-29 11:29:11', 1, 3, 7.40, 1.90, 0.60, NULL, 120, 393, 3871, 1.00, 28.8, 778, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (800, '2025-09-28 08:53:11', 1, 3, 7.10, 0.70, 2.60, NULL, 84, 234, 3938, 0.60, 29.0, 655, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (801, '2025-09-27 08:20:11', 1, 3, 7.60, 0.70, 1.60, NULL, 100, 228, 3898, 0.90, 26.5, 774, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (802, '2025-09-26 08:09:11', 1, 3, 7.70, 0.50, 0.70, NULL, 109, 204, 3921, 0.20, 29.4, 685, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (803, '2025-09-25 10:39:11', 1, 3, 7.60, 2.50, 1.00, NULL, 83, 265, 3024, 0.00, 29.1, 660, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (804, '2025-09-24 08:03:11', 1, 3, 7.30, 1.10, 2.30, NULL, 107, 282, 3319, 0.50, 27.3, 688, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (805, '2025-09-23 10:46:11', 1, 3, 7.20, 2.60, 3.20, NULL, 96, 245, 3185, 0.30, 26.0, 692, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (806, '2025-09-22 09:05:11', 1, 3, 7.50, 2.50, 1.30, NULL, 101, 400, 3068, 0.10, 29.9, 777, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (807, '2025-09-21 10:09:11', 1, 3, 7.30, 2.60, 1.20, NULL, 116, 399, 3213, 0.30, 26.0, 662, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (808, '2025-09-20 10:35:11', 1, 3, 7.70, 0.80, 2.20, NULL, 84, 222, 3964, 1.00, 26.7, 715, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (809, '2025-09-19 10:06:11', 1, 3, 7.50, 0.90, 0.70, NULL, 103, 267, 3311, 0.40, 29.7, 750, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (810, '2025-09-18 11:28:11', 1, 3, 7.80, 1.30, 3.00, NULL, 87, 332, 3539, 0.50, 28.9, 666, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (811, '2025-09-17 09:54:11', 1, 3, 7.30, 1.30, 2.30, NULL, 115, 395, 3752, 0.40, 26.4, 756, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (812, '2025-09-16 11:26:11', 1, 3, 7.80, 0.70, 3.30, NULL, 106, 368, 3936, 0.10, 28.7, 664, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (813, '2025-09-15 08:20:11', 1, 3, 7.00, 1.50, 1.30, NULL, 86, 343, 3250, 0.90, 27.7, 752, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (814, '2025-09-14 09:34:11', 1, 3, 7.60, 1.90, 0.60, NULL, 108, 304, 3835, 0.70, 26.7, 778, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (815, '2025-09-13 09:51:11', 1, 3, 7.40, 0.80, 1.10, NULL, 80, 361, 3824, 0.80, 26.8, 754, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (816, '2025-09-12 09:44:11', 1, 3, 7.80, 1.50, 3.40, NULL, 100, 292, 3836, 0.40, 27.1, 699, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (817, '2025-09-11 10:55:11', 1, 3, 7.40, 2.20, 1.60, NULL, 107, 365, 3518, 1.00, 29.2, 756, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (818, '2025-09-10 09:07:11', 1, 3, 7.40, 1.20, 3.50, NULL, 81, 333, 3431, 1.00, 26.9, 683, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (819, '2025-09-09 11:11:11', 1, 3, 7.00, 1.80, 1.50, NULL, 120, 291, 3540, 0.50, 29.8, 769, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (820, '2025-09-08 09:15:11', 1, 3, 7.70, 0.80, 3.30, NULL, 100, 390, 3132, 0.40, 29.6, 796, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (821, '2025-09-07 09:36:11', 1, 3, 7.40, 2.20, 1.00, NULL, 107, 221, 3570, 0.60, 26.3, 693, 'Routine monthly check', '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (822, '2025-09-06 10:23:11', 1, 3, 7.40, 1.40, 0.90, NULL, 109, 396, 3794, 0.30, 29.6, 655, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (823, '2025-09-05 10:10:11', 1, 3, 7.00, 1.50, 1.20, NULL, 87, 371, 3573, 1.00, 26.1, 762, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (824, '2025-09-04 08:46:11', 1, 3, 7.00, 1.90, 2.60, NULL, 114, 202, 3705, 0.80, 26.6, 767, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (825, '2025-09-03 08:42:11', 1, 3, 7.40, 1.20, 2.30, NULL, 117, 392, 3969, 0.50, 30.0, 732, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (826, '2025-09-02 10:31:11', 1, 3, 7.60, 2.60, 1.10, NULL, 107, 392, 3321, 0.40, 27.4, 776, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (827, '2025-09-01 11:44:11', 1, 3, 7.10, 1.30, 1.70, NULL, 92, 316, 3949, 0.30, 29.5, 663, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (828, '2025-08-31 10:00:11', 1, 3, 7.70, 2.50, 1.80, NULL, 86, 254, 3853, 0.20, 26.7, 674, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (829, '2025-08-30 11:42:11', 1, 3, 7.80, 1.50, 1.30, NULL, 98, 319, 3892, 0.80, 29.3, 777, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (830, '2025-08-29 08:17:11', 1, 3, 7.40, 2.80, 0.60, NULL, 94, 379, 3116, 1.00, 27.0, 714, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (831, '2025-08-28 10:34:11', 1, 3, 7.20, 2.50, 1.10, NULL, 95, 287, 3734, 0.20, 26.3, 654, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (832, '2025-08-27 11:32:11', 1, 3, 7.60, 1.90, 2.10, NULL, 83, 335, 3756, 0.40, 27.4, 651, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (833, '2025-08-26 09:41:11', 1, 3, 7.10, 2.20, 2.80, NULL, 100, 293, 3830, 0.90, 28.0, 720, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (834, '2025-08-25 10:58:11', 1, 3, 7.60, 1.90, 2.50, NULL, 113, 397, 3943, 0.60, 29.0, 677, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (835, '2025-08-24 09:21:11', 1, 3, 7.40, 2.30, 1.50, NULL, 91, 397, 3906, 0.60, 28.7, 797, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (836, '2025-08-23 11:19:11', 1, 3, 7.80, 0.50, 3.40, NULL, 97, 313, 3704, 0.30, 28.7, 747, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (837, '2025-08-22 11:40:11', 1, 3, 7.20, 0.60, 2.90, NULL, 101, 341, 3953, 1.00, 29.2, 741, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (838, '2025-08-21 10:44:11', 1, 3, 7.20, 1.40, 1.40, NULL, 82, 269, 3986, 1.00, 27.7, 791, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (839, '2025-08-20 09:59:11', 1, 3, 7.70, 2.50, 1.90, NULL, 86, 219, 3351, 0.00, 29.2, 712, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (840, '2025-08-19 10:44:11', 1, 3, 7.00, 1.60, 1.00, NULL, 88, 339, 3785, 1.00, 27.7, 716, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (841, '2025-08-18 11:25:11', 1, 3, 7.20, 2.80, 3.40, NULL, 85, 263, 3241, 0.40, 28.8, 770, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (842, '2025-08-17 08:59:11', 1, 3, 7.30, 2.50, 1.50, NULL, 92, 346, 3482, 0.00, 29.7, 768, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (843, '2025-08-16 09:50:11', 1, 3, 7.20, 1.20, 2.40, NULL, 118, 347, 3646, 0.70, 28.6, 765, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (844, '2025-08-15 11:08:11', 1, 3, 7.00, 1.90, 2.70, NULL, 88, 342, 3366, 1.00, 27.6, 720, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (845, '2025-08-14 11:41:11', 1, 3, 7.00, 1.90, 0.90, NULL, 118, 301, 3702, 0.10, 28.6, 719, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (846, '2025-08-13 09:00:11', 1, 3, 7.00, 1.60, 3.00, NULL, 85, 236, 3385, 0.20, 29.4, 729, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (847, '2025-08-12 11:13:11', 1, 3, 7.30, 1.30, 0.80, NULL, 90, 266, 3584, 0.40, 28.1, 684, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (848, '2025-08-11 08:54:11', 1, 3, 7.10, 2.80, 1.40, NULL, 120, 258, 3940, 0.40, 29.7, 751, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (849, '2025-08-10 11:37:11', 1, 3, 7.70, 1.70, 3.40, NULL, 102, 381, 3966, 0.00, 26.2, 723, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (850, '2025-08-09 11:06:11', 1, 3, 7.80, 2.60, 0.70, NULL, 89, 254, 3305, 0.30, 26.1, 683, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (851, '2025-08-08 11:28:11', 1, 3, 7.10, 2.90, 1.30, NULL, 117, 301, 3623, 0.10, 26.9, 680, 'Routine monthly check', '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (852, '2025-08-07 10:23:11', 1, 3, 7.50, 1.40, 2.10, NULL, 82, 232, 3027, 0.60, 27.7, 728, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (853, '2025-08-06 08:05:11', 1, 3, 7.60, 0.50, 2.70, NULL, 120, 322, 3546, 0.30, 27.2, 692, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (854, '2025-08-05 08:38:11', 1, 3, 7.70, 1.70, 1.70, NULL, 94, 246, 3048, 0.90, 28.0, 702, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (855, '2025-08-04 09:30:11', 1, 3, 7.40, 0.70, 1.40, NULL, 93, 393, 3047, 0.60, 28.4, 785, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (856, '2025-08-03 09:04:11', 1, 3, 7.80, 2.10, 2.10, NULL, 90, 223, 3781, 0.50, 27.4, 762, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (857, '2025-08-02 11:36:11', 1, 3, 7.60, 1.10, 0.90, NULL, 93, 221, 3925, 1.00, 30.0, 671, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (858, '2025-08-01 11:03:11', 1, 3, 7.50, 2.60, 2.90, NULL, 87, 297, 3721, 0.20, 27.4, 696, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (859, '2025-07-31 11:45:11', 1, 3, 7.50, 1.60, 1.30, NULL, 105, 329, 3843, 0.30, 26.6, 722, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (860, '2025-07-30 10:08:11', 1, 3, 7.60, 1.60, 2.20, NULL, 105, 312, 3570, 0.20, 26.2, 653, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (861, '2025-07-29 09:17:11', 1, 3, 7.40, 2.80, 1.70, NULL, 98, 381, 3110, 0.60, 29.9, 663, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (862, '2025-07-28 09:01:11', 1, 3, 7.20, 1.40, 3.10, NULL, 104, 276, 3409, 0.60, 29.3, 769, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (863, '2025-07-27 08:10:11', 1, 3, 7.10, 1.20, 2.90, NULL, 87, 259, 3576, 0.20, 29.5, 672, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (864, '2025-07-26 10:01:11', 1, 3, 7.30, 2.40, 1.40, NULL, 113, 226, 3847, 0.50, 28.3, 661, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (865, '2025-07-25 11:04:11', 1, 3, 7.60, 1.20, 2.60, NULL, 98, 384, 3997, 1.00, 28.3, 768, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (866, '2025-07-24 09:01:11', 1, 3, 7.10, 2.10, 3.10, NULL, 116, 360, 3263, 0.60, 29.1, 660, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (867, '2025-07-23 09:40:11', 1, 3, 7.60, 2.20, 2.60, NULL, 96, 204, 3567, 0.90, 28.8, 686, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (868, '2025-07-22 08:37:11', 1, 3, 7.20, 2.30, 2.50, NULL, 99, 219, 3864, 0.90, 26.3, 735, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (869, '2025-07-21 09:31:11', 1, 3, 7.60, 1.50, 2.00, NULL, 93, 273, 3907, 0.90, 28.0, 800, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (870, '2025-07-20 09:50:11', 1, 3, 7.10, 1.00, 2.90, NULL, 119, 380, 3989, 0.70, 30.0, 768, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (871, '2025-07-19 08:10:11', 1, 3, 7.00, 2.60, 2.70, NULL, 119, 232, 3672, 0.70, 26.2, 750, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (872, '2025-07-18 09:07:11', 1, 3, 7.70, 0.90, 1.20, NULL, 91, 358, 3317, 0.00, 26.8, 744, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (873, '2025-07-17 09:44:11', 1, 3, 7.10, 2.80, 0.50, NULL, 106, 337, 3044, 0.30, 29.5, 711, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (874, '2025-07-16 09:25:11', 1, 3, 7.20, 2.50, 1.00, NULL, 114, 256, 3488, 0.30, 27.4, 724, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (875, '2025-07-15 11:13:11', 1, 3, 7.30, 2.50, 2.80, NULL, 94, 214, 3378, 0.20, 27.2, 732, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (876, '2025-07-14 08:38:11', 1, 3, 7.00, 2.80, 2.20, NULL, 96, 367, 3431, 0.20, 28.2, 651, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (877, '2025-07-13 08:01:11', 1, 3, 7.80, 2.10, 1.70, NULL, 101, 325, 3439, 0.80, 27.1, 775, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (878, '2025-07-12 08:14:11', 1, 3, 7.50, 2.40, 2.80, NULL, 102, 353, 3728, 0.20, 30.0, 707, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (879, '2025-07-11 09:45:11', 1, 3, 7.50, 1.70, 1.00, NULL, 93, 213, 3589, 0.10, 29.5, 665, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (880, '2025-07-10 09:52:11', 1, 3, 7.00, 2.10, 0.80, NULL, 80, 295, 3554, 0.80, 29.3, 653, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (881, '2025-07-09 10:35:11', 1, 3, 7.20, 2.80, 0.50, NULL, 92, 301, 3519, 1.00, 26.4, 664, 'Routine monthly check', '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (882, '2025-07-08 10:14:11', 1, 3, 7.30, 1.30, 0.50, NULL, 105, 277, 3436, 0.80, 26.8, 732, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (883, '2025-07-07 11:20:11', 1, 3, 7.00, 0.90, 1.10, NULL, 112, 304, 3979, 0.80, 26.2, 779, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (884, '2025-07-06 08:57:11', 1, 3, 7.50, 1.70, 1.40, NULL, 99, 332, 3885, 0.40, 27.0, 738, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (885, '2025-07-05 09:09:11', 1, 3, 7.10, 2.70, 0.70, NULL, 90, 325, 3908, 0.50, 27.1, 713, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (886, '2025-07-04 10:22:11', 1, 3, 7.00, 2.60, 1.40, NULL, 83, 384, 3868, 0.50, 29.3, 768, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (887, '2025-07-03 10:49:11', 1, 3, 7.50, 1.60, 2.80, NULL, 87, 319, 3027, 0.10, 26.1, 728, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (888, '2025-07-02 09:55:11', 1, 3, 7.80, 2.90, 1.20, NULL, 94, 278, 3741, 0.40, 29.5, 725, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (889, '2025-07-01 11:10:11', 1, 3, 7.00, 0.60, 1.80, NULL, 89, 301, 3875, 0.20, 27.2, 673, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (890, '2025-06-30 08:58:11', 1, 3, 7.70, 1.10, 3.40, NULL, 114, 380, 3307, 1.00, 29.7, 791, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (891, '2025-06-29 11:45:11', 1, 3, 7.10, 2.40, 2.00, NULL, 82, 300, 3297, 0.20, 27.4, 666, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (892, '2025-06-28 11:15:11', 1, 3, 7.40, 2.70, 2.10, NULL, 88, 312, 3801, 0.50, 28.1, 767, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (893, '2025-06-27 11:11:11', 1, 3, 7.40, 0.70, 0.70, NULL, 118, 388, 3690, 0.60, 28.2, 761, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (894, '2025-06-26 09:08:11', 1, 3, 7.70, 1.40, 2.00, NULL, 97, 219, 3915, 0.00, 28.1, 750, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (895, '2025-06-25 09:34:11', 1, 3, 7.00, 0.60, 2.00, NULL, 80, 291, 3786, 0.00, 27.6, 701, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (896, '2025-06-24 11:55:11', 1, 3, 7.60, 1.60, 3.30, NULL, 92, 256, 3759, 0.70, 28.1, 773, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (897, '2025-06-23 08:23:11', 1, 3, 7.40, 0.60, 2.20, NULL, 81, 304, 3917, 0.90, 28.6, 776, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (898, '2025-06-22 09:09:11', 1, 3, 7.10, 2.60, 0.60, NULL, 107, 208, 3394, 0.50, 26.3, 704, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (899, '2025-06-21 08:43:11', 1, 3, 7.40, 1.90, 3.50, NULL, 82, 338, 3767, 0.20, 26.0, 756, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (900, '2025-06-20 11:26:11', 1, 3, 7.60, 2.00, 1.70, NULL, 89, 290, 3249, 0.80, 27.9, 686, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (901, '2025-06-19 11:54:11', 1, 3, 7.10, 0.70, 2.10, NULL, 106, 235, 3477, 1.00, 27.1, 723, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (902, '2025-06-18 10:27:11', 1, 3, 7.10, 2.10, 1.20, NULL, 86, 214, 3445, 0.80, 29.5, 744, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (903, '2025-06-17 09:47:11', 1, 3, 7.20, 2.80, 3.50, NULL, 94, 299, 3654, 0.90, 26.5, 754, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (904, '2025-06-16 11:09:11', 1, 3, 7.40, 2.90, 0.80, NULL, 100, 252, 3258, 1.00, 27.2, 761, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (905, '2025-06-15 08:14:11', 1, 3, 7.40, 1.80, 3.50, NULL, 83, 215, 3825, 0.90, 27.7, 724, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (906, '2025-06-14 11:37:11', 1, 3, 7.80, 2.20, 3.30, NULL, 85, 334, 3474, 0.10, 29.7, 789, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (907, '2025-06-13 10:36:11', 1, 3, 7.40, 0.90, 0.90, NULL, 95, 261, 3196, 0.80, 26.1, 681, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (908, '2025-06-12 11:03:11', 1, 3, 7.10, 1.90, 3.20, NULL, 85, 334, 3131, 0.80, 26.2, 724, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (909, '2025-06-11 11:56:11', 1, 3, 7.40, 1.80, 0.80, NULL, 87, 313, 3760, 1.00, 27.8, 784, NULL, '2025-12-06 12:13:11', '2025-12-06 12:13:11');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (910, '2025-06-10 08:52:12', 1, 3, 7.70, 2.10, 1.80, NULL, 94, 201, 3564, 0.70, 27.3, 736, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (911, '2025-06-09 08:53:12', 1, 3, 7.40, 0.60, 2.60, NULL, 102, 234, 3469, 0.60, 27.1, 723, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (912, '2025-06-08 11:36:12', 1, 3, 7.80, 2.80, 2.70, NULL, 97, 398, 3296, 0.90, 26.4, 749, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (913, '2025-06-07 08:08:12', 1, 3, 7.60, 1.40, 3.40, NULL, 104, 287, 3502, 0.80, 28.9, 678, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (914, '2025-06-06 08:48:12', 1, 3, 7.70, 2.00, 2.00, NULL, 87, 393, 3402, 0.40, 27.8, 683, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (915, '2025-06-05 09:24:12', 1, 3, 7.00, 0.70, 0.50, NULL, 86, 288, 3098, 0.40, 26.0, 757, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (916, '2025-06-04 11:43:12', 1, 3, 7.30, 1.00, 1.90, NULL, 82, 383, 3061, 0.30, 27.2, 711, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (917, '2025-06-03 08:42:12', 1, 3, 7.50, 0.60, 0.50, NULL, 102, 294, 3975, 0.30, 27.3, 706, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (918, '2025-06-02 11:18:12', 1, 3, 7.00, 3.00, 1.90, NULL, 101, 230, 3502, 0.30, 28.6, 794, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (919, '2025-06-01 10:26:12', 1, 3, 7.40, 1.30, 2.00, NULL, 102, 305, 3149, 0.20, 29.2, 707, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (920, '2025-05-31 11:41:12', 1, 3, 7.50, 2.10, 3.00, NULL, 84, 278, 3035, 1.00, 27.8, 657, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (921, '2025-05-30 09:07:12', 1, 3, 7.30, 0.90, 2.60, NULL, 85, 291, 3911, 1.00, 26.2, 785, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (922, '2025-05-29 08:14:12', 1, 3, 7.30, 2.10, 0.50, NULL, 103, 269, 3609, 0.10, 29.8, 677, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (923, '2025-05-28 11:34:12', 1, 3, 7.60, 0.70, 3.20, NULL, 107, 299, 3470, 0.60, 27.0, 656, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (924, '2025-05-27 09:55:12', 1, 3, 7.80, 0.90, 3.50, NULL, 113, 257, 3461, 0.30, 27.1, 764, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (925, '2025-05-26 11:52:12', 1, 3, 7.30, 0.50, 3.00, NULL, 101, 211, 3413, 0.70, 26.9, 692, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (926, '2025-05-25 10:43:12', 1, 3, 7.00, 1.80, 0.80, NULL, 119, 214, 3577, 0.10, 27.1, 752, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (927, '2025-05-24 11:48:12', 1, 3, 7.00, 2.10, 2.70, NULL, 112, 262, 3358, 0.50, 28.8, 776, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (928, '2025-05-23 08:14:12', 1, 3, 7.00, 1.50, 2.30, NULL, 114, 264, 3335, 0.50, 27.5, 668, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (929, '2025-05-22 10:07:12', 1, 3, 7.00, 1.00, 2.10, NULL, 117, 353, 3461, 0.70, 28.2, 662, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (930, '2025-05-21 11:50:12', 1, 3, 7.40, 2.90, 2.80, NULL, 107, 279, 3526, 0.50, 27.2, 793, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (931, '2025-05-20 11:13:12', 1, 3, 7.70, 1.30, 0.50, NULL, 89, 282, 3925, 0.40, 29.2, 748, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (932, '2025-05-19 10:05:12', 1, 3, 7.10, 0.50, 3.30, NULL, 91, 324, 3886, 0.10, 27.8, 651, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (933, '2025-05-18 10:27:12', 1, 3, 7.10, 1.20, 1.30, NULL, 82, 371, 3802, 0.60, 26.4, 684, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (934, '2025-05-17 11:59:12', 1, 3, 7.00, 3.00, 2.10, NULL, 82, 355, 3989, 0.30, 27.0, 794, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (935, '2025-05-16 08:23:12', 1, 3, 7.50, 1.30, 2.50, NULL, 91, 353, 3472, 1.00, 26.5, 737, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (936, '2025-05-15 08:32:12', 1, 3, 7.60, 2.80, 1.60, NULL, 97, 271, 3565, 0.20, 26.2, 655, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (937, '2025-05-14 08:00:12', 1, 3, 7.50, 3.00, 3.50, NULL, 108, 276, 3116, 0.80, 27.4, 724, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (938, '2025-05-13 08:46:12', 1, 3, 7.80, 2.30, 3.50, NULL, 100, 284, 3547, 0.00, 27.6, 727, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (939, '2025-05-12 10:27:12', 1, 3, 7.30, 1.20, 1.20, NULL, 80, 362, 3843, 0.80, 27.0, 787, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (940, '2025-05-11 10:51:12', 1, 3, 7.10, 1.00, 3.50, NULL, 119, 228, 3725, 1.00, 27.6, 673, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (941, '2025-05-10 08:41:12', 1, 3, 7.20, 1.20, 3.00, NULL, 106, 336, 3289, 0.90, 28.6, 654, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (942, '2025-05-09 10:14:12', 1, 3, 7.60, 1.60, 1.60, NULL, 108, 387, 3268, 0.50, 28.5, 695, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (943, '2025-05-08 09:08:12', 1, 3, 7.10, 0.70, 1.70, NULL, 102, 279, 3956, 0.70, 28.2, 666, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (944, '2025-05-07 08:55:12', 1, 3, 7.80, 2.90, 0.70, NULL, 90, 243, 3759, 0.30, 29.8, 668, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (945, '2025-05-06 10:57:12', 1, 3, 7.50, 1.40, 3.20, NULL, 80, 331, 3568, 0.80, 27.9, 664, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (946, '2025-05-05 08:25:12', 1, 3, 7.20, 2.30, 3.30, NULL, 117, 374, 3007, 0.10, 27.3, 763, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (947, '2025-05-04 09:12:12', 1, 3, 7.10, 1.70, 2.40, NULL, 104, 254, 3837, 0.20, 26.3, 727, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (948, '2025-05-03 11:13:12', 1, 3, 7.40, 1.80, 2.50, NULL, 93, 221, 3006, 0.00, 27.2, 762, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (949, '2025-05-02 08:14:12', 1, 3, 7.50, 1.10, 1.50, NULL, 108, 233, 3008, 0.10, 29.4, 680, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (950, '2025-05-01 11:28:12', 1, 3, 7.50, 1.80, 2.50, NULL, 103, 348, 3812, 0.30, 28.4, 717, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (951, '2025-04-30 11:34:12', 1, 3, 7.20, 2.70, 0.80, NULL, 117, 394, 3495, 0.10, 29.1, 772, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (952, '2025-04-29 11:29:12', 1, 3, 7.80, 2.50, 1.00, NULL, 119, 279, 3273, 0.90, 28.9, 765, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (953, '2025-04-28 11:02:12', 1, 3, 7.80, 2.10, 1.10, NULL, 114, 273, 3369, 0.00, 27.8, 672, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (954, '2025-04-27 09:03:12', 1, 3, 7.60, 1.20, 3.40, NULL, 95, 259, 3590, 0.60, 29.5, 769, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (955, '2025-04-26 10:44:12', 1, 3, 7.00, 1.20, 2.20, NULL, 110, 267, 3094, 0.50, 27.8, 730, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (956, '2025-04-25 11:40:12', 1, 3, 7.00, 2.20, 1.00, NULL, 80, 365, 3881, 0.00, 27.1, 670, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (957, '2025-04-24 08:22:12', 1, 3, 7.50, 0.90, 3.40, NULL, 108, 313, 3783, 0.00, 26.9, 733, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (958, '2025-04-23 09:33:12', 1, 3, 7.00, 2.70, 2.50, NULL, 86, 203, 3226, 0.60, 27.2, 781, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (959, '2025-04-22 11:42:12', 1, 3, 7.60, 1.10, 1.40, NULL, 112, 250, 3211, 1.00, 29.9, 668, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (960, '2025-04-21 09:59:12', 1, 3, 7.20, 1.90, 3.00, NULL, 92, 336, 3071, 0.10, 28.6, 653, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (961, '2025-04-20 09:08:12', 1, 3, 7.30, 2.90, 2.70, NULL, 103, 394, 3969, 0.60, 27.4, 723, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (962, '2025-04-19 09:52:12', 1, 3, 7.70, 1.30, 1.20, NULL, 104, 384, 3788, 0.50, 27.5, 683, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (963, '2025-04-18 08:45:12', 1, 3, 7.30, 2.30, 2.00, NULL, 95, 367, 3288, 1.00, 27.5, 729, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (964, '2025-04-17 10:58:12', 1, 3, 7.50, 1.30, 2.90, NULL, 111, 282, 3565, 0.00, 27.1, 751, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (965, '2025-04-16 09:55:12', 1, 3, 7.30, 2.00, 2.60, NULL, 103, 327, 3765, 0.00, 26.6, 756, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (966, '2025-04-15 08:46:12', 1, 3, 7.00, 3.00, 1.10, NULL, 96, 301, 3361, 0.20, 26.0, 789, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (967, '2025-04-14 10:24:12', 1, 3, 7.60, 1.10, 2.10, NULL, 90, 276, 3985, 0.10, 29.2, 763, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (968, '2025-04-13 08:24:12', 1, 3, 7.50, 0.60, 1.80, NULL, 81, 202, 3789, 0.70, 29.0, 731, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (969, '2025-04-12 11:06:12', 1, 3, 7.10, 1.90, 0.50, NULL, 82, 330, 3053, 0.70, 28.6, 657, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (970, '2025-04-11 10:00:12', 1, 3, 7.60, 2.70, 0.80, NULL, 94, 265, 3845, 0.60, 26.1, 700, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (971, '2025-04-10 10:01:12', 1, 3, 7.70, 2.50, 3.10, NULL, 110, 247, 3569, 1.00, 28.5, 788, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (972, '2025-04-09 09:13:12', 1, 3, 7.60, 1.00, 1.60, NULL, 89, 337, 3967, 0.10, 27.5, 788, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (973, '2025-04-08 08:59:12', 1, 3, 7.20, 1.20, 1.10, NULL, 115, 263, 3072, 0.40, 26.5, 698, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (974, '2025-04-07 08:58:12', 1, 3, 7.70, 1.30, 2.30, NULL, 83, 388, 3180, 0.50, 26.9, 722, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (975, '2025-04-06 10:49:12', 1, 3, 7.40, 1.10, 1.10, NULL, 108, 230, 3680, 0.50, 26.5, 713, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (976, '2025-04-05 10:45:12', 1, 3, 7.60, 2.30, 3.30, NULL, 99, 326, 3403, 0.50, 26.3, 729, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (977, '2025-04-04 11:30:12', 1, 3, 7.70, 1.80, 2.10, NULL, 83, 280, 3043, 1.00, 26.4, 746, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (978, '2025-04-03 10:40:12', 1, 3, 7.60, 2.50, 2.10, NULL, 119, 332, 3525, 0.60, 29.8, 745, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (979, '2025-04-02 09:41:12', 1, 3, 7.20, 0.90, 0.50, NULL, 107, 366, 3732, 1.00, 28.7, 694, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (980, '2025-04-01 11:28:12', 1, 3, 7.70, 0.70, 0.80, NULL, 108, 244, 3303, 0.30, 29.3, 798, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (981, '2025-03-31 10:14:12', 1, 3, 7.40, 0.80, 1.10, NULL, 105, 353, 3034, 0.80, 28.7, 708, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (982, '2025-03-30 10:13:12', 1, 3, 7.60, 0.90, 3.10, NULL, 80, 308, 3291, 0.20, 29.1, 681, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (983, '2025-03-29 08:27:12', 1, 3, 7.80, 1.30, 2.30, NULL, 85, 393, 3738, 0.30, 26.1, 772, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (984, '2025-03-28 09:59:12', 1, 3, 7.10, 2.60, 3.20, NULL, 117, 291, 3719, 0.00, 29.7, 665, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (985, '2025-03-27 10:07:12', 1, 3, 7.20, 0.70, 2.90, NULL, 80, 279, 3872, 0.20, 29.8, 697, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (986, '2025-03-26 09:23:12', 1, 3, 7.10, 2.10, 2.80, NULL, 112, 241, 3556, 0.00, 26.9, 726, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (987, '2025-03-25 09:28:12', 1, 3, 7.60, 1.60, 2.50, NULL, 108, 232, 3797, 0.30, 29.3, 659, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (988, '2025-03-24 10:58:12', 1, 3, 7.50, 2.20, 0.50, NULL, 84, 372, 3046, 0.20, 29.5, 664, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (989, '2025-03-23 08:06:12', 1, 3, 7.80, 1.90, 3.30, NULL, 84, 351, 3104, 0.00, 26.7, 650, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (990, '2025-03-22 09:53:12', 1, 3, 7.40, 1.70, 1.10, NULL, 84, 362, 3032, 0.70, 26.5, 684, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (991, '2025-03-21 11:49:12', 1, 3, 7.40, 2.20, 0.70, NULL, 90, 207, 3678, 0.40, 26.4, 770, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (992, '2025-03-20 09:06:12', 1, 3, 7.50, 2.90, 0.50, NULL, 102, 316, 3390, 1.00, 26.5, 718, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (993, '2025-03-19 11:05:12', 1, 3, 7.20, 2.80, 2.70, NULL, 83, 321, 3188, 0.00, 27.2, 704, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (994, '2025-03-18 08:28:12', 1, 3, 7.20, 2.20, 1.80, NULL, 93, 277, 3996, 0.20, 28.8, 700, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (995, '2025-03-17 10:20:12', 1, 3, 7.60, 2.00, 1.90, NULL, 99, 324, 3085, 0.80, 27.3, 710, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (996, '2025-03-16 08:37:12', 1, 3, 7.20, 2.00, 1.30, NULL, 92, 312, 3021, 0.90, 29.2, 787, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (997, '2025-03-15 09:55:12', 1, 3, 7.30, 2.10, 1.40, NULL, 108, 211, 3730, 0.00, 28.2, 749, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (998, '2025-03-14 11:49:12', 1, 3, 7.10, 1.50, 2.20, NULL, 84, 247, 3936, 0.80, 27.0, 725, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (999, '2025-03-13 10:28:12', 1, 3, 7.70, 1.20, 0.50, NULL, 103, 249, 3501, 0.80, 27.5, 762, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1000, '2025-03-12 10:40:12', 1, 3, 7.10, 2.90, 0.80, NULL, 118, 227, 3967, 0.20, 29.2, 673, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1001, '2025-03-11 10:15:12', 1, 3, 7.70, 1.30, 2.70, NULL, 108, 386, 3749, 0.80, 27.2, 723, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1002, '2025-03-10 08:42:12', 1, 3, 7.20, 1.90, 2.10, NULL, 119, 375, 3788, 0.50, 27.2, 725, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1003, '2025-03-09 11:41:12', 1, 3, 7.20, 0.70, 2.00, NULL, 114, 251, 3189, 0.30, 26.1, 679, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1004, '2025-03-08 11:18:12', 1, 3, 7.80, 1.20, 2.70, NULL, 105, 204, 3065, 0.40, 29.8, 660, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1005, '2025-03-07 09:56:12', 1, 3, 7.80, 2.20, 3.40, NULL, 101, 366, 3785, 0.90, 29.4, 678, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1006, '2025-03-06 08:52:12', 1, 3, 7.20, 2.90, 0.80, NULL, 119, 235, 3910, 0.10, 27.4, 736, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1007, '2025-03-05 10:09:12', 1, 3, 7.20, 1.10, 0.60, NULL, 92, 342, 3600, 0.90, 26.0, 683, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1008, '2025-03-04 09:24:12', 1, 3, 7.10, 1.00, 1.30, NULL, 98, 337, 3696, 0.00, 27.7, 652, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1009, '2025-03-03 10:50:12', 1, 3, 7.20, 2.20, 0.90, NULL, 98, 278, 3774, 0.80, 27.2, 779, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1010, '2025-03-02 11:56:12', 1, 3, 7.30, 1.90, 2.20, NULL, 106, 241, 3271, 0.50, 26.5, 694, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1011, '2025-03-01 09:48:12', 1, 3, 7.40, 0.50, 1.50, NULL, 109, 214, 3278, 0.90, 26.2, 739, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1012, '2025-02-28 08:43:12', 1, 3, 7.20, 1.20, 0.70, NULL, 109, 351, 3532, 0.40, 28.3, 785, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1013, '2025-02-27 08:39:12', 1, 3, 7.00, 0.50, 1.00, NULL, 80, 258, 3068, 0.60, 27.2, 710, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1014, '2025-02-26 11:36:12', 1, 3, 7.10, 2.30, 1.50, NULL, 88, 360, 3396, 0.90, 27.3, 732, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1015, '2025-02-25 09:34:12', 1, 3, 7.40, 0.60, 0.70, NULL, 108, 331, 3016, 0.20, 26.0, 722, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1016, '2025-02-24 11:27:12', 1, 3, 7.40, 2.80, 3.20, NULL, 85, 397, 3106, 0.80, 28.7, 735, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1017, '2025-02-23 09:31:12', 1, 3, 7.60, 1.90, 2.40, NULL, 113, 293, 3134, 0.30, 28.2, 782, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1018, '2025-02-22 10:56:12', 1, 3, 7.10, 2.30, 2.10, NULL, 105, 336, 3111, 0.50, 26.2, 716, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1019, '2025-02-21 11:01:12', 1, 3, 7.30, 1.30, 2.00, NULL, 80, 243, 3918, 0.40, 29.5, 704, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1020, '2025-02-20 10:57:12', 1, 3, 7.80, 0.60, 1.30, NULL, 103, 360, 3889, 0.80, 28.1, 758, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1021, '2025-02-19 08:22:12', 1, 3, 7.00, 2.70, 0.90, NULL, 80, 387, 3433, 0.30, 28.1, 718, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1022, '2025-02-18 08:57:12', 1, 3, 7.40, 1.20, 2.10, NULL, 102, 349, 3391, 0.60, 28.6, 742, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1023, '2025-02-17 10:09:12', 1, 3, 7.10, 2.90, 3.30, NULL, 118, 257, 3774, 0.30, 26.9, 721, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1024, '2025-02-16 09:55:12', 1, 3, 7.20, 0.50, 1.10, NULL, 95, 272, 3359, 0.10, 27.9, 739, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1025, '2025-02-15 08:03:12', 1, 3, 7.00, 2.80, 2.70, NULL, 86, 393, 3245, 0.60, 27.2, 683, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1026, '2025-02-14 11:37:12', 1, 3, 7.50, 0.50, 1.40, NULL, 119, 359, 3106, 0.90, 28.0, 728, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1027, '2025-02-13 08:24:12', 1, 3, 7.00, 2.70, 1.60, NULL, 84, 280, 3604, 0.70, 27.8, 730, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1028, '2025-02-12 08:25:12', 1, 3, 7.30, 1.20, 1.40, NULL, 94, 300, 3828, 0.70, 26.6, 768, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1029, '2025-02-11 11:06:12', 1, 3, 7.70, 1.60, 2.50, NULL, 112, 327, 3374, 0.80, 27.2, 696, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1030, '2025-02-10 08:09:12', 1, 3, 7.20, 2.30, 1.20, NULL, 105, 308, 3047, 0.80, 26.0, 794, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1031, '2025-02-09 10:37:12', 1, 3, 7.70, 1.90, 2.70, NULL, 82, 356, 3248, 0.60, 29.0, 691, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1032, '2025-02-08 10:56:12', 1, 3, 7.80, 1.70, 3.50, NULL, 97, 213, 3545, 0.80, 28.6, 691, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1033, '2025-02-07 08:25:12', 1, 3, 7.20, 1.20, 2.20, NULL, 81, 359, 3422, 0.20, 27.8, 710, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1034, '2025-02-06 11:48:12', 1, 3, 7.30, 1.00, 2.30, NULL, 96, 286, 3996, 0.50, 26.8, 799, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1035, '2025-02-05 11:20:12', 1, 3, 7.30, 0.60, 3.30, NULL, 90, 331, 3340, 0.60, 29.9, 784, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1036, '2025-02-04 11:23:12', 1, 3, 7.10, 1.70, 0.90, NULL, 102, 264, 3325, 0.30, 28.7, 681, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1037, '2025-02-03 10:26:12', 1, 3, 7.00, 1.80, 1.40, NULL, 119, 310, 3074, 1.00, 28.0, 790, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1038, '2025-02-02 10:28:12', 1, 3, 7.30, 1.90, 2.70, NULL, 115, 326, 3747, 1.00, 26.7, 741, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1039, '2025-02-01 09:29:12', 1, 3, 7.80, 2.80, 2.80, NULL, 95, 391, 3856, 0.90, 28.7, 719, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1040, '2025-01-31 11:00:12', 1, 3, 7.30, 1.70, 1.50, NULL, 92, 335, 3140, 0.90, 27.2, 665, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1041, '2025-01-30 08:39:12', 1, 3, 7.40, 2.20, 3.00, NULL, 112, 213, 3010, 0.10, 26.3, 691, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1042, '2025-01-29 11:37:12', 1, 3, 7.10, 1.40, 3.40, NULL, 104, 213, 3360, 0.00, 28.7, 657, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1043, '2025-01-28 08:19:12', 1, 3, 7.40, 3.00, 3.50, NULL, 119, 362, 3602, 0.60, 26.9, 679, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1044, '2025-01-27 08:58:12', 1, 3, 7.80, 2.30, 2.10, NULL, 109, 325, 3760, 0.00, 29.1, 673, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1045, '2025-01-26 10:20:12', 1, 3, 7.10, 1.80, 2.60, NULL, 105, 265, 3941, 0.30, 29.2, 659, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1046, '2025-01-25 08:12:12', 1, 3, 7.00, 2.70, 1.90, NULL, 84, 210, 3408, 0.30, 27.9, 758, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1047, '2025-01-24 08:11:12', 1, 3, 7.40, 2.50, 0.50, NULL, 86, 273, 3015, 0.00, 28.5, 762, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1048, '2025-01-23 08:11:12', 1, 3, 7.30, 1.50, 0.80, NULL, 97, 270, 3520, 0.50, 28.7, 650, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1049, '2025-01-22 09:35:12', 1, 3, 7.30, 0.50, 0.60, NULL, 98, 286, 3874, 0.40, 29.1, 685, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1050, '2025-01-21 09:25:12', 1, 3, 7.60, 3.00, 1.90, NULL, 83, 387, 3247, 0.20, 27.9, 743, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1051, '2025-01-20 10:45:12', 1, 3, 7.10, 1.70, 2.50, NULL, 89, 346, 3323, 0.40, 26.8, 720, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1052, '2025-01-19 10:10:12', 1, 3, 7.40, 0.70, 3.00, NULL, 109, 204, 3204, 1.00, 27.5, 726, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1053, '2025-01-18 09:50:12', 1, 3, 7.40, 2.50, 3.40, NULL, 89, 399, 3012, 0.80, 28.2, 750, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1054, '2025-01-17 10:32:12', 1, 3, 7.00, 0.80, 1.70, NULL, 106, 367, 3335, 0.40, 29.7, 663, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1055, '2025-01-16 11:30:12', 1, 3, 7.00, 0.50, 1.80, NULL, 97, 351, 3396, 0.00, 28.1, 674, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1056, '2025-01-15 09:35:12', 1, 3, 7.50, 2.60, 0.60, NULL, 99, 225, 3619, 0.60, 29.2, 793, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1057, '2025-01-14 10:14:12', 1, 3, 7.70, 1.60, 1.50, NULL, 94, 241, 3598, 0.10, 26.1, 736, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1058, '2025-01-13 08:55:12', 1, 3, 7.10, 2.50, 1.10, NULL, 80, 308, 3297, 0.80, 27.9, 711, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1059, '2025-01-12 09:12:12', 1, 3, 7.00, 1.30, 3.50, NULL, 112, 208, 3102, 0.30, 29.7, 704, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1060, '2025-01-11 10:39:12', 1, 3, 7.10, 0.80, 2.90, NULL, 96, 266, 3366, 0.60, 28.1, 748, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1061, '2025-01-10 09:01:12', 1, 3, 7.20, 1.10, 1.20, NULL, 80, 209, 3095, 0.50, 26.1, 659, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1062, '2025-01-09 08:44:12', 1, 3, 7.70, 1.60, 3.20, NULL, 85, 304, 3275, 0.80, 28.4, 753, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1063, '2025-01-08 08:03:12', 1, 3, 7.60, 2.80, 2.20, NULL, 83, 301, 3942, 0.70, 26.9, 723, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1064, '2025-01-07 11:01:12', 1, 3, 7.70, 1.90, 2.30, NULL, 85, 279, 3328, 0.10, 29.1, 654, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1065, '2025-01-06 08:35:12', 1, 3, 7.80, 3.00, 3.40, NULL, 116, 267, 3425, 0.00, 27.8, 661, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1066, '2025-01-05 09:54:12', 1, 3, 7.20, 2.00, 0.80, NULL, 97, 399, 3890, 0.20, 27.2, 755, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1067, '2025-01-04 09:49:12', 1, 3, 7.50, 1.80, 2.90, NULL, 98, 361, 3214, 0.00, 29.8, 770, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1068, '2025-01-03 11:25:12', 1, 3, 7.60, 3.00, 3.30, NULL, 111, 312, 3947, 0.10, 29.1, 771, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1069, '2025-01-02 11:30:12', 1, 3, 7.40, 2.30, 1.90, NULL, 105, 201, 3553, 0.90, 28.2, 702, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1070, '2025-01-01 09:02:12', 1, 3, 7.00, 2.90, 1.00, NULL, 92, 291, 3950, 0.10, 28.3, 665, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1071, '2024-12-31 08:00:12', 1, 3, 7.00, 1.50, 2.00, NULL, 115, 347, 3909, 0.10, 27.9, 800, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1072, '2024-12-30 08:45:12', 1, 3, 7.10, 2.50, 1.00, NULL, 90, 340, 3128, 0.60, 27.6, 652, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1073, '2024-12-29 08:25:12', 1, 3, 7.20, 2.90, 2.10, NULL, 115, 389, 3781, 0.10, 29.8, 749, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1074, '2024-12-28 10:36:12', 1, 3, 7.70, 2.70, 3.40, NULL, 99, 292, 3912, 0.00, 26.5, 660, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1075, '2024-12-27 09:18:12', 1, 3, 7.70, 0.50, 3.40, NULL, 104, 253, 3919, 0.70, 27.1, 735, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1076, '2024-12-26 09:17:12', 1, 3, 7.30, 1.20, 3.40, NULL, 99, 251, 3952, 0.50, 27.2, 664, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1077, '2024-12-25 11:26:12', 1, 3, 7.30, 2.50, 3.50, NULL, 90, 354, 3165, 0.30, 29.7, 771, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1078, '2024-12-24 11:12:12', 1, 3, 7.10, 0.60, 1.00, NULL, 84, 259, 3602, 0.80, 29.0, 666, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1079, '2024-12-23 11:17:12', 1, 3, 7.20, 0.60, 3.20, NULL, 90, 262, 3611, 1.00, 27.2, 675, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1080, '2024-12-22 09:52:12', 1, 3, 7.40, 2.40, 1.80, NULL, 120, 253, 3920, 0.20, 27.9, 793, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1081, '2024-12-21 11:27:12', 1, 3, 7.20, 1.10, 3.50, NULL, 82, 376, 3038, 0.00, 26.4, 759, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1082, '2024-12-20 09:56:12', 1, 3, 7.20, 2.50, 3.40, NULL, 120, 301, 3706, 0.70, 26.4, 697, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1083, '2024-12-19 08:54:12', 1, 3, 7.60, 2.00, 0.80, NULL, 105, 359, 3970, 0.50, 29.6, 789, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1084, '2024-12-18 11:37:12', 1, 3, 7.30, 2.20, 2.60, NULL, 81, 203, 3577, 0.20, 29.7, 734, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1085, '2024-12-17 09:49:12', 1, 3, 7.70, 0.80, 0.70, NULL, 109, 251, 3257, 0.50, 29.4, 728, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1086, '2024-12-16 10:26:12', 1, 3, 7.70, 1.20, 3.30, NULL, 95, 334, 3434, 0.60, 26.3, 709, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1087, '2024-12-15 11:03:12', 1, 3, 7.60, 1.20, 0.70, NULL, 82, 207, 3636, 0.70, 26.6, 733, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1088, '2024-12-14 11:26:12', 1, 3, 7.10, 0.80, 0.90, NULL, 118, 235, 3516, 0.70, 26.7, 724, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1089, '2024-12-13 09:19:12', 1, 3, 7.40, 1.30, 1.40, NULL, 119, 385, 3080, 0.90, 26.3, 758, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1090, '2024-12-12 10:45:12', 1, 3, 7.50, 1.40, 1.80, NULL, 102, 213, 3740, 0.20, 29.0, 742, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1091, '2024-12-11 09:01:12', 1, 3, 7.50, 0.90, 0.50, NULL, 92, 295, 3191, 0.00, 28.7, 740, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1092, '2024-12-10 11:07:12', 1, 3, 7.40, 1.60, 1.40, NULL, 103, 278, 3638, 0.10, 28.4, 753, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1093, '2024-12-09 10:00:12', 1, 3, 7.70, 0.90, 3.40, NULL, 106, 339, 3219, 0.60, 29.2, 731, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1094, '2024-12-08 10:41:12', 1, 3, 7.10, 2.40, 2.80, NULL, 104, 251, 3113, 0.90, 26.8, 747, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1095, '2024-12-07 11:25:12', 1, 3, 7.10, 0.60, 1.80, NULL, 98, 335, 3362, 0.50, 27.0, 650, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1096, '2025-12-06 09:30:12', 1, 4, 7.70, 2.10, 2.90, NULL, 93, 295, 3086, 0.90, 26.2, 683, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1097, '2025-12-05 10:29:12', 1, 4, 7.20, 0.50, 2.00, NULL, 102, 285, 3281, 0.50, 27.0, 773, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1098, '2025-12-04 08:16:12', 1, 4, 7.50, 0.80, 2.50, NULL, 82, 400, 3490, 0.30, 27.8, 691, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1099, '2025-12-03 11:58:12', 1, 4, 7.80, 1.00, 3.20, NULL, 98, 295, 3903, 0.90, 26.7, 767, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1100, '2025-12-02 10:28:12', 1, 4, 7.80, 1.50, 1.00, NULL, 97, 301, 3685, 1.00, 29.0, 685, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1101, '2025-12-01 11:35:12', 1, 4, 7.70, 3.00, 2.00, NULL, 114, 304, 3254, 0.00, 30.0, 774, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1102, '2025-11-30 11:12:12', 1, 4, 7.00, 1.70, 0.70, NULL, 86, 285, 3993, 0.80, 27.9, 692, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1103, '2025-11-29 10:10:12', 1, 4, 7.50, 1.90, 1.00, NULL, 93, 350, 3986, 0.70, 28.0, 710, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1104, '2025-11-28 09:44:12', 1, 4, 7.70, 0.60, 0.70, NULL, 80, 363, 3778, 0.90, 26.8, 766, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1105, '2025-11-27 10:27:12', 1, 4, 7.00, 2.80, 1.10, NULL, 101, 244, 3839, 0.10, 26.7, 689, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1106, '2025-11-26 08:18:12', 1, 4, 7.50, 1.40, 0.90, NULL, 108, 360, 3284, 0.60, 28.9, 686, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1107, '2025-11-25 11:13:12', 1, 4, 7.00, 1.30, 3.00, NULL, 109, 206, 3063, 0.00, 28.2, 727, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1108, '2025-11-24 11:36:12', 1, 4, 7.40, 2.20, 3.30, NULL, 107, 218, 3198, 0.60, 30.0, 773, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1109, '2025-11-23 09:30:12', 1, 4, 7.10, 2.70, 2.20, NULL, 110, 337, 3100, 0.80, 27.8, 701, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1110, '2025-11-22 09:08:12', 1, 4, 7.40, 1.70, 2.00, NULL, 109, 300, 3962, 0.70, 27.9, 684, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1111, '2025-11-21 11:41:12', 1, 4, 7.40, 1.50, 2.70, NULL, 107, 320, 3072, 0.60, 28.2, 677, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1112, '2025-11-20 11:15:12', 1, 4, 7.20, 2.60, 3.20, NULL, 117, 303, 3729, 0.80, 27.0, 750, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1113, '2025-11-19 09:00:12', 1, 4, 7.10, 2.90, 1.30, NULL, 90, 378, 3520, 0.50, 29.9, 795, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1114, '2025-11-18 11:38:12', 1, 4, 7.80, 2.20, 2.90, NULL, 108, 384, 3059, 0.50, 28.5, 730, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1115, '2025-11-17 09:06:12', 1, 4, 7.10, 2.10, 0.80, NULL, 94, 248, 3029, 1.00, 27.9, 716, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1116, '2025-11-16 09:47:12', 1, 4, 7.40, 1.00, 3.20, NULL, 118, 227, 3253, 0.40, 29.7, 716, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1117, '2025-11-15 09:45:12', 1, 4, 7.60, 1.10, 3.00, NULL, 84, 337, 3156, 0.70, 29.3, 705, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1118, '2025-11-14 11:03:12', 1, 4, 7.50, 1.40, 0.80, NULL, 81, 258, 3902, 0.40, 26.7, 691, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1119, '2025-11-13 09:47:12', 1, 4, 7.10, 1.10, 1.10, NULL, 97, 205, 3113, 0.70, 27.7, 757, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1120, '2025-11-12 08:44:12', 1, 4, 7.10, 2.30, 1.50, NULL, 94, 210, 3174, 0.20, 29.6, 652, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1121, '2025-11-11 09:00:12', 1, 4, 7.20, 1.80, 2.00, NULL, 91, 261, 3264, 0.00, 26.5, 761, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1122, '2025-11-10 10:17:12', 1, 4, 7.10, 0.70, 0.80, NULL, 101, 400, 3657, 0.20, 26.5, 777, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1123, '2025-11-09 10:46:12', 1, 4, 7.60, 1.40, 2.20, NULL, 96, 296, 3343, 0.30, 26.4, 672, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1124, '2025-11-08 08:50:12', 1, 4, 7.30, 2.90, 2.80, NULL, 118, 201, 3221, 0.20, 26.8, 719, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1125, '2025-11-07 09:05:12', 1, 4, 7.20, 1.60, 0.60, NULL, 85, 260, 3486, 0.30, 26.3, 659, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1126, '2025-11-06 08:45:12', 1, 4, 7.20, 3.00, 2.40, NULL, 83, 327, 3209, 0.70, 28.1, 656, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1127, '2025-11-05 09:42:12', 1, 4, 7.30, 1.20, 2.20, NULL, 107, 318, 3645, 0.10, 27.3, 709, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1128, '2025-11-04 10:53:12', 1, 4, 7.10, 1.50, 2.70, NULL, 103, 309, 3209, 0.00, 26.2, 718, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1129, '2025-11-03 08:37:12', 1, 4, 7.00, 2.00, 1.00, NULL, 92, 290, 3260, 0.00, 27.2, 700, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1130, '2025-11-02 11:00:12', 1, 4, 7.80, 1.80, 3.30, NULL, 116, 281, 3050, 0.40, 28.4, 762, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1131, '2025-11-01 10:01:12', 1, 4, 7.60, 2.90, 3.10, NULL, 100, 274, 3244, 0.60, 28.0, 704, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1132, '2025-10-31 10:34:12', 1, 4, 7.40, 2.00, 1.30, NULL, 117, 261, 3921, 0.80, 26.4, 695, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1133, '2025-10-30 10:42:12', 1, 4, 7.40, 1.80, 1.50, NULL, 95, 262, 3497, 0.10, 26.2, 745, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1134, '2025-10-29 10:45:12', 1, 4, 7.10, 2.00, 2.40, NULL, 102, 259, 3238, 0.30, 29.9, 695, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1135, '2025-10-28 08:33:12', 1, 4, 7.00, 2.90, 1.50, NULL, 86, 304, 3206, 0.20, 26.1, 793, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1136, '2025-10-27 09:49:12', 1, 4, 7.30, 1.00, 2.80, NULL, 97, 232, 3049, 0.70, 26.4, 784, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1137, '2025-10-26 09:58:12', 1, 4, 7.70, 1.20, 1.70, NULL, 88, 359, 3113, 0.00, 26.1, 661, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1138, '2025-10-25 10:05:12', 1, 4, 7.30, 2.50, 1.60, NULL, 98, 214, 3576, 0.00, 28.4, 696, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1139, '2025-10-24 10:47:12', 1, 4, 7.00, 3.00, 1.60, NULL, 80, 333, 3864, 0.00, 27.6, 711, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1140, '2025-10-23 11:07:12', 1, 4, 7.10, 2.30, 1.10, NULL, 102, 258, 3685, 0.50, 26.8, 772, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1141, '2025-10-22 10:29:12', 1, 4, 7.80, 2.90, 2.80, NULL, 83, 236, 3679, 0.40, 28.8, 740, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1142, '2025-10-21 09:38:12', 1, 4, 7.10, 2.40, 1.50, NULL, 97, 282, 3754, 0.20, 29.7, 786, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1143, '2025-10-20 08:50:12', 1, 4, 7.80, 2.80, 1.00, NULL, 116, 367, 3457, 1.00, 29.8, 793, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1144, '2025-10-19 08:16:12', 1, 4, 7.10, 1.00, 2.70, NULL, 120, 329, 3972, 0.60, 28.4, 742, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1145, '2025-10-18 10:39:12', 1, 4, 7.00, 1.50, 1.30, NULL, 80, 302, 3659, 0.20, 27.1, 704, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1146, '2025-10-17 08:19:12', 1, 4, 7.30, 1.90, 1.10, NULL, 113, 234, 3424, 0.00, 26.7, 776, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1147, '2025-10-16 09:21:12', 1, 4, 7.80, 2.20, 0.70, NULL, 86, 333, 3979, 0.20, 28.7, 746, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1148, '2025-10-15 11:58:12', 1, 4, 7.60, 1.00, 1.80, NULL, 106, 382, 3280, 0.80, 26.2, 799, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1149, '2025-10-14 11:25:12', 1, 4, 7.60, 2.00, 2.40, NULL, 104, 254, 3701, 0.30, 29.1, 711, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1150, '2025-10-13 08:12:12', 1, 4, 7.00, 0.80, 0.90, NULL, 97, 220, 3397, 0.00, 28.1, 745, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1151, '2025-10-12 11:44:12', 1, 4, 7.30, 2.60, 2.80, NULL, 81, 354, 3413, 0.20, 27.4, 750, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1152, '2025-10-11 11:19:12', 1, 4, 7.30, 0.50, 3.30, NULL, 81, 222, 3608, 0.70, 28.9, 743, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1153, '2025-10-10 10:36:12', 1, 4, 7.70, 2.20, 2.50, NULL, 82, 384, 3755, 0.90, 28.2, 676, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1154, '2025-10-09 09:35:12', 1, 4, 7.70, 1.10, 2.60, NULL, 111, 375, 3105, 0.00, 26.7, 777, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1155, '2025-10-08 08:47:12', 1, 4, 7.30, 1.90, 2.40, NULL, 98, 241, 3977, 0.00, 29.7, 773, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1156, '2025-10-07 11:14:12', 1, 4, 7.00, 2.50, 0.50, NULL, 95, 269, 3130, 0.70, 29.9, 796, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1157, '2025-10-06 08:27:12', 1, 4, 7.10, 0.80, 3.30, NULL, 93, 373, 3910, 1.00, 29.2, 692, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1158, '2025-10-05 11:39:12', 1, 4, 7.30, 2.80, 3.50, NULL, 113, 269, 3190, 0.30, 28.0, 674, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1159, '2025-10-04 08:41:12', 1, 4, 7.40, 2.80, 2.40, NULL, 81, 333, 3072, 0.10, 27.9, 727, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1160, '2025-10-03 09:50:12', 1, 4, 7.80, 1.60, 2.80, NULL, 117, 238, 3827, 0.40, 26.9, 759, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1161, '2025-10-02 09:43:12', 1, 4, 7.20, 3.00, 1.30, NULL, 85, 371, 3616, 0.60, 27.6, 721, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1162, '2025-10-01 08:38:12', 1, 4, 7.10, 0.60, 1.90, NULL, 106, 224, 3139, 0.00, 27.1, 731, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1163, '2025-09-30 11:53:12', 1, 4, 7.20, 1.60, 0.70, NULL, 95, 283, 3778, 0.20, 27.5, 784, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1164, '2025-09-29 09:46:12', 1, 4, 7.70, 0.50, 2.80, NULL, 107, 240, 3752, 0.20, 26.5, 755, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1165, '2025-09-28 11:00:12', 1, 4, 7.60, 2.80, 0.60, NULL, 82, 248, 3775, 1.00, 29.7, 672, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1166, '2025-09-27 11:46:12', 1, 4, 7.40, 0.70, 2.10, NULL, 84, 399, 3690, 0.40, 30.0, 761, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1167, '2025-09-26 10:07:12', 1, 4, 7.50, 1.10, 2.30, NULL, 84, 382, 3933, 0.50, 29.6, 764, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1168, '2025-09-25 11:38:12', 1, 4, 7.60, 0.60, 3.30, NULL, 112, 354, 3748, 0.30, 26.2, 683, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1169, '2025-09-24 09:58:12', 1, 4, 7.80, 2.40, 1.30, NULL, 87, 207, 3740, 1.00, 29.2, 667, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1170, '2025-09-23 10:02:12', 1, 4, 7.50, 1.60, 2.50, NULL, 92, 399, 3694, 0.70, 29.5, 749, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1171, '2025-09-22 11:32:12', 1, 4, 7.40, 2.80, 0.50, NULL, 107, 269, 3544, 0.10, 29.5, 674, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1172, '2025-09-21 08:41:12', 1, 4, 7.70, 0.60, 0.80, NULL, 81, 337, 3515, 0.20, 26.3, 736, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1173, '2025-09-20 09:56:12', 1, 4, 7.30, 2.30, 1.40, NULL, 96, 320, 3463, 0.50, 29.6, 767, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1174, '2025-09-19 11:50:12', 1, 4, 7.20, 1.80, 2.20, NULL, 106, 342, 3406, 0.80, 28.5, 713, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1175, '2025-09-18 09:34:12', 1, 4, 7.50, 2.60, 1.70, NULL, 112, 325, 3444, 1.00, 26.1, 658, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1176, '2025-09-17 10:17:12', 1, 4, 7.20, 2.80, 1.70, NULL, 105, 308, 3246, 0.00, 28.0, 717, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1177, '2025-09-16 10:16:12', 1, 4, 7.20, 3.00, 0.70, NULL, 120, 368, 3772, 0.40, 28.0, 791, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1178, '2025-09-15 11:05:12', 1, 4, 7.70, 2.80, 3.50, NULL, 112, 262, 3679, 0.60, 27.5, 651, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1179, '2025-09-14 08:26:12', 1, 4, 7.20, 0.70, 0.60, NULL, 113, 220, 3277, 0.70, 29.3, 776, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1180, '2025-09-13 08:15:12', 1, 4, 7.40, 1.10, 3.30, NULL, 109, 391, 3307, 0.30, 27.6, 655, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1181, '2025-09-12 11:02:12', 1, 4, 7.00, 2.20, 1.80, NULL, 111, 249, 3881, 0.70, 27.9, 740, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1182, '2025-09-11 10:26:12', 1, 4, 7.10, 0.90, 3.10, NULL, 89, 390, 3846, 0.40, 27.6, 798, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1183, '2025-09-10 11:04:12', 1, 4, 7.70, 2.20, 3.10, NULL, 91, 328, 3707, 0.80, 28.7, 714, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1184, '2025-09-09 09:40:12', 1, 4, 7.00, 0.50, 1.40, NULL, 83, 368, 3246, 0.80, 27.1, 667, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1185, '2025-09-08 08:19:12', 1, 4, 7.30, 1.00, 0.60, NULL, 113, 399, 3923, 0.90, 27.5, 652, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1186, '2025-09-07 09:40:12', 1, 4, 7.50, 2.10, 2.40, NULL, 110, 206, 3094, 0.80, 29.8, 662, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1187, '2025-09-06 08:43:12', 1, 4, 7.00, 0.60, 1.20, NULL, 116, 393, 3422, 0.90, 29.8, 705, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1188, '2025-09-05 08:28:12', 1, 4, 7.20, 1.70, 2.90, NULL, 86, 377, 3297, 1.00, 29.3, 715, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1189, '2025-09-04 11:27:12', 1, 4, 7.00, 1.00, 2.40, NULL, 95, 373, 3694, 0.00, 27.4, 720, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1190, '2025-09-03 09:26:12', 1, 4, 7.70, 2.50, 3.00, NULL, 94, 291, 3809, 1.00, 29.6, 681, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1191, '2025-09-02 10:01:12', 1, 4, 7.00, 1.20, 1.80, NULL, 110, 225, 3840, 0.90, 27.5, 688, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1192, '2025-09-01 11:08:12', 1, 4, 7.40, 1.10, 3.10, NULL, 120, 361, 3189, 0.10, 26.2, 659, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1193, '2025-08-31 11:04:12', 1, 4, 7.80, 1.10, 2.20, NULL, 106, 268, 3939, 0.20, 27.5, 673, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1194, '2025-08-30 10:22:12', 1, 4, 7.50, 0.70, 1.60, NULL, 108, 337, 3381, 0.10, 29.8, 732, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1195, '2025-08-29 10:48:12', 1, 4, 7.20, 1.40, 2.20, NULL, 108, 260, 3479, 0.70, 29.1, 736, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1196, '2025-08-28 11:53:12', 1, 4, 7.80, 2.10, 2.80, NULL, 83, 317, 3965, 0.40, 28.3, 722, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1197, '2025-08-27 11:07:12', 1, 4, 7.20, 0.60, 1.30, NULL, 118, 292, 3085, 0.60, 27.2, 743, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1198, '2025-08-26 08:38:12', 1, 4, 7.20, 1.20, 1.80, NULL, 90, 361, 3039, 0.10, 28.9, 793, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1199, '2025-08-25 10:42:12', 1, 4, 7.80, 2.50, 2.40, NULL, 82, 394, 3241, 0.50, 29.4, 724, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1200, '2025-08-24 08:50:12', 1, 4, 7.70, 1.90, 0.50, NULL, 117, 388, 3625, 0.70, 27.0, 740, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1201, '2025-08-23 11:37:12', 1, 4, 7.80, 0.80, 3.40, NULL, 89, 306, 3946, 0.80, 26.9, 775, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1202, '2025-08-22 11:47:12', 1, 4, 7.00, 1.20, 3.10, NULL, 83, 398, 3638, 0.20, 26.0, 725, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1203, '2025-08-21 11:36:12', 1, 4, 7.30, 1.30, 0.70, NULL, 102, 400, 3717, 0.20, 28.6, 784, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1204, '2025-08-20 09:53:12', 1, 4, 7.30, 2.00, 3.30, NULL, 98, 261, 3912, 0.50, 26.4, 678, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1205, '2025-08-19 11:14:12', 1, 4, 7.10, 1.80, 2.10, NULL, 88, 265, 3543, 0.00, 27.5, 737, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1206, '2025-08-18 11:43:12', 1, 4, 7.30, 2.70, 2.90, NULL, 120, 213, 3704, 0.60, 29.9, 763, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1207, '2025-08-17 11:41:12', 1, 4, 7.50, 2.10, 3.20, NULL, 98, 246, 3403, 0.40, 27.9, 751, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1208, '2025-08-16 10:55:12', 1, 4, 7.20, 0.90, 3.00, NULL, 104, 373, 3795, 0.00, 27.5, 728, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1209, '2025-08-15 09:23:12', 1, 4, 7.50, 2.60, 2.20, NULL, 91, 382, 3636, 0.80, 27.1, 792, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1210, '2025-08-14 10:15:12', 1, 4, 7.00, 1.60, 1.10, NULL, 90, 363, 3070, 1.00, 28.0, 696, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1211, '2025-08-13 08:47:12', 1, 4, 7.20, 2.70, 3.50, NULL, 107, 357, 3678, 0.90, 28.3, 767, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1212, '2025-08-12 08:19:12', 1, 4, 7.10, 2.30, 1.60, NULL, 117, 361, 3576, 1.00, 28.1, 788, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1213, '2025-08-11 10:43:12', 1, 4, 7.80, 1.30, 0.90, NULL, 92, 343, 3017, 0.70, 26.0, 776, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1214, '2025-08-10 08:53:12', 1, 4, 7.60, 2.30, 0.50, NULL, 105, 387, 3775, 0.20, 27.9, 677, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1215, '2025-08-09 10:20:12', 1, 4, 7.60, 2.30, 2.90, NULL, 98, 297, 3245, 0.20, 28.2, 713, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1216, '2025-08-08 09:59:12', 1, 4, 7.00, 1.80, 0.70, NULL, 100, 395, 3431, 0.80, 26.5, 721, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1217, '2025-08-07 10:16:12', 1, 4, 7.80, 0.80, 1.00, NULL, 84, 268, 3427, 0.30, 28.1, 719, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1218, '2025-08-06 11:16:12', 1, 4, 7.70, 1.70, 2.70, NULL, 97, 306, 3919, 0.30, 28.8, 674, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1219, '2025-08-05 11:16:12', 1, 4, 7.30, 2.90, 2.60, NULL, 108, 209, 3542, 0.70, 26.4, 717, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1220, '2025-08-04 08:18:12', 1, 4, 7.80, 2.70, 3.00, NULL, 97, 217, 3846, 0.20, 29.1, 715, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1221, '2025-08-03 09:25:12', 1, 4, 7.20, 2.70, 1.10, NULL, 111, 340, 3348, 0.90, 29.6, 694, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1222, '2025-08-02 11:59:12', 1, 4, 7.00, 2.80, 2.20, NULL, 80, 242, 3889, 0.80, 26.9, 698, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1223, '2025-08-01 11:30:12', 1, 4, 7.60, 2.90, 1.00, NULL, 92, 235, 3673, 0.00, 28.7, 706, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1224, '2025-07-31 11:37:12', 1, 4, 7.10, 1.70, 2.30, NULL, 86, 215, 3483, 0.10, 26.3, 710, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1225, '2025-07-30 10:35:12', 1, 4, 7.20, 2.10, 1.40, NULL, 118, 322, 3829, 0.30, 27.1, 764, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1226, '2025-07-29 11:32:12', 1, 4, 7.30, 2.00, 0.80, NULL, 104, 272, 3041, 1.00, 26.7, 749, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1227, '2025-07-28 10:58:12', 1, 4, 7.20, 1.60, 2.50, NULL, 112, 221, 3210, 0.50, 27.8, 710, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1228, '2025-07-27 11:08:12', 1, 4, 7.70, 2.40, 1.40, NULL, 109, 201, 3595, 0.60, 29.9, 661, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1229, '2025-07-26 10:44:12', 1, 4, 7.00, 0.50, 0.50, NULL, 88, 303, 3109, 0.70, 29.7, 707, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1230, '2025-07-25 11:45:12', 1, 4, 7.20, 1.00, 2.40, NULL, 118, 219, 3598, 0.30, 27.4, 667, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1231, '2025-07-24 08:26:12', 1, 4, 7.50, 1.20, 1.10, NULL, 98, 246, 3618, 0.00, 28.2, 765, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1232, '2025-07-23 09:58:12', 1, 4, 7.40, 0.80, 1.20, NULL, 111, 307, 3546, 0.60, 27.4, 779, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1233, '2025-07-22 08:00:12', 1, 4, 7.60, 1.90, 2.40, NULL, 93, 286, 3617, 1.00, 28.5, 674, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1234, '2025-07-21 09:44:12', 1, 4, 7.70, 1.70, 2.20, NULL, 86, 309, 3909, 0.10, 27.1, 720, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1235, '2025-07-20 09:08:12', 1, 4, 7.80, 2.30, 2.90, NULL, 95, 378, 3478, 0.70, 27.7, 690, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1236, '2025-07-19 08:41:12', 1, 4, 7.20, 2.80, 1.00, NULL, 95, 274, 3377, 0.30, 29.9, 797, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1237, '2025-07-18 08:35:12', 1, 4, 7.20, 1.80, 3.00, NULL, 85, 273, 3968, 0.00, 27.8, 796, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1238, '2025-07-17 10:58:12', 1, 4, 7.50, 1.40, 3.40, NULL, 82, 294, 3824, 0.70, 29.4, 740, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1239, '2025-07-16 08:11:12', 1, 4, 7.80, 1.90, 1.50, NULL, 103, 302, 3330, 0.60, 26.5, 767, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1240, '2025-07-15 11:39:12', 1, 4, 7.10, 2.70, 1.90, NULL, 120, 269, 3298, 0.70, 29.8, 727, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1241, '2025-07-14 10:58:12', 1, 4, 7.70, 2.20, 3.50, NULL, 82, 226, 3382, 1.00, 26.5, 727, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1242, '2025-07-13 08:13:12', 1, 4, 7.80, 0.50, 2.80, NULL, 96, 357, 3109, 0.20, 27.0, 688, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1243, '2025-07-12 11:59:12', 1, 4, 7.00, 0.70, 2.40, NULL, 83, 218, 3770, 1.00, 28.3, 661, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1244, '2025-07-11 09:41:12', 1, 4, 7.30, 2.60, 3.30, NULL, 113, 315, 3967, 0.50, 27.2, 714, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1245, '2025-07-10 10:25:12', 1, 4, 7.50, 1.40, 1.20, NULL, 84, 200, 3179, 0.50, 29.7, 782, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1246, '2025-07-09 11:13:12', 1, 4, 7.10, 1.00, 2.20, NULL, 98, 343, 3194, 0.50, 30.0, 654, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1247, '2025-07-08 10:41:12', 1, 4, 7.00, 1.20, 1.70, NULL, 96, 258, 3715, 0.70, 26.2, 750, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1248, '2025-07-07 11:14:12', 1, 4, 7.80, 2.50, 2.80, NULL, 107, 237, 3314, 0.60, 27.3, 713, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1249, '2025-07-06 09:42:12', 1, 4, 7.10, 1.70, 3.00, NULL, 96, 275, 3790, 0.00, 26.2, 650, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1250, '2025-07-05 11:37:12', 1, 4, 7.60, 2.60, 2.30, NULL, 92, 343, 3392, 0.30, 27.0, 661, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1251, '2025-07-04 10:14:12', 1, 4, 7.80, 1.60, 1.00, NULL, 89, 272, 3603, 0.20, 27.6, 797, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1252, '2025-07-03 09:18:12', 1, 4, 7.50, 2.10, 2.80, NULL, 104, 216, 3386, 0.80, 27.6, 754, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1253, '2025-07-02 09:02:12', 1, 4, 7.20, 2.00, 2.10, NULL, 117, 328, 3638, 0.00, 27.1, 797, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1254, '2025-07-01 10:48:12', 1, 4, 7.30, 2.10, 2.30, NULL, 103, 289, 3230, 0.20, 27.7, 677, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1255, '2025-06-30 11:18:12', 1, 4, 7.50, 1.10, 3.10, NULL, 119, 317, 3664, 0.30, 28.0, 676, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1256, '2025-06-29 10:17:12', 1, 4, 7.20, 1.10, 2.30, NULL, 89, 221, 3406, 0.20, 26.4, 746, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1257, '2025-06-28 08:39:12', 1, 4, 7.50, 2.90, 3.30, NULL, 84, 285, 3097, 0.50, 29.9, 761, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1258, '2025-06-27 08:19:12', 1, 4, 7.30, 1.20, 2.80, NULL, 96, 368, 3460, 0.80, 27.3, 653, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1259, '2025-06-26 08:30:12', 1, 4, 7.80, 1.20, 1.50, NULL, 90, 213, 3688, 0.30, 26.2, 651, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1260, '2025-06-25 11:29:12', 1, 4, 7.80, 0.60, 0.90, NULL, 117, 307, 3620, 1.00, 26.4, 788, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1261, '2025-06-24 11:11:12', 1, 4, 7.00, 1.50, 1.80, NULL, 94, 273, 3735, 0.50, 28.4, 651, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1262, '2025-06-23 10:36:12', 1, 4, 7.50, 2.50, 0.50, NULL, 110, 349, 3605, 0.70, 26.1, 696, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1263, '2025-06-22 11:28:12', 1, 4, 7.60, 2.30, 1.30, NULL, 89, 220, 3326, 1.00, 27.2, 679, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1264, '2025-06-21 11:25:12', 1, 4, 7.50, 0.50, 0.90, NULL, 114, 389, 3585, 0.10, 27.6, 798, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1265, '2025-06-20 10:05:12', 1, 4, 7.80, 2.30, 1.10, NULL, 91, 302, 3958, 0.20, 28.9, 702, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1266, '2025-06-19 08:27:12', 1, 4, 7.20, 2.20, 3.50, NULL, 105, 223, 3481, 0.10, 27.6, 779, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1267, '2025-06-18 11:36:12', 1, 4, 7.80, 3.00, 1.30, NULL, 99, 295, 3173, 1.00, 28.4, 750, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1268, '2025-06-17 08:20:12', 1, 4, 7.10, 2.70, 2.80, NULL, 101, 327, 3748, 0.80, 27.4, 795, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1269, '2025-06-16 08:11:12', 1, 4, 7.00, 2.90, 1.40, NULL, 81, 252, 3106, 1.00, 29.6, 764, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1270, '2025-06-15 09:11:12', 1, 4, 7.40, 1.20, 3.50, NULL, 113, 342, 3083, 0.20, 29.4, 739, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1271, '2025-06-14 08:35:12', 1, 4, 7.80, 2.50, 0.90, NULL, 94, 396, 3669, 0.60, 28.3, 751, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1272, '2025-06-13 08:11:12', 1, 4, 7.20, 0.70, 0.60, NULL, 101, 383, 3282, 1.00, 29.2, 702, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1273, '2025-06-12 08:01:12', 1, 4, 7.60, 1.20, 2.00, NULL, 112, 258, 3157, 0.20, 27.2, 766, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1274, '2025-06-11 08:50:12', 1, 4, 7.20, 2.20, 3.10, NULL, 110, 209, 3920, 0.50, 26.2, 744, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1275, '2025-06-10 11:36:12', 1, 4, 7.40, 2.10, 0.60, NULL, 116, 279, 3108, 0.00, 26.3, 776, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1276, '2025-06-09 08:24:12', 1, 4, 7.10, 0.80, 1.90, NULL, 110, 314, 3623, 0.50, 26.4, 774, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1277, '2025-06-08 10:02:12', 1, 4, 7.70, 1.50, 3.40, NULL, 97, 394, 3488, 0.30, 26.1, 761, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1278, '2025-06-07 08:32:12', 1, 4, 7.80, 2.90, 3.00, NULL, 100, 292, 3180, 0.70, 29.6, 759, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1279, '2025-06-06 09:24:12', 1, 4, 7.80, 2.30, 1.20, NULL, 105, 305, 3348, 0.10, 27.0, 710, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1280, '2025-06-05 08:34:12', 1, 4, 7.30, 1.00, 1.70, NULL, 90, 298, 3262, 0.40, 29.2, 796, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1281, '2025-06-04 09:48:12', 1, 4, 7.00, 1.10, 2.30, NULL, 109, 285, 3507, 0.90, 26.4, 743, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1282, '2025-06-03 10:09:12', 1, 4, 7.60, 2.80, 3.50, NULL, 98, 241, 3827, 0.30, 28.1, 693, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1283, '2025-06-02 11:28:12', 1, 4, 7.60, 2.80, 2.20, NULL, 111, 257, 3170, 0.80, 29.3, 730, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1284, '2025-06-01 11:40:12', 1, 4, 7.50, 1.40, 0.50, NULL, 96, 268, 3982, 0.80, 29.1, 710, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1285, '2025-05-31 11:19:12', 1, 4, 7.80, 2.00, 1.40, NULL, 88, 384, 3658, 0.70, 26.0, 692, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1286, '2025-05-30 08:53:12', 1, 4, 7.40, 1.10, 2.00, NULL, 113, 262, 3934, 0.50, 28.6, 781, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1287, '2025-05-29 11:54:12', 1, 4, 7.50, 1.80, 2.50, NULL, 101, 237, 3519, 0.10, 26.1, 662, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1288, '2025-05-28 09:44:12', 1, 4, 7.40, 1.70, 2.00, NULL, 89, 390, 3553, 0.70, 29.0, 723, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1289, '2025-05-27 08:24:12', 1, 4, 7.30, 0.70, 1.00, NULL, 81, 282, 3832, 0.60, 29.3, 704, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1290, '2025-05-26 10:13:12', 1, 4, 7.50, 2.90, 0.70, NULL, 109, 370, 3905, 0.80, 27.8, 778, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1291, '2025-05-25 09:31:12', 1, 4, 7.60, 2.30, 2.20, NULL, 92, 367, 3845, 0.00, 29.1, 733, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1292, '2025-05-24 09:53:12', 1, 4, 7.60, 1.20, 0.70, NULL, 118, 361, 3928, 0.30, 27.4, 762, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1293, '2025-05-23 10:16:12', 1, 4, 7.20, 1.10, 1.30, NULL, 99, 350, 3973, 0.90, 28.9, 797, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1294, '2025-05-22 10:45:12', 1, 4, 7.60, 1.80, 2.00, NULL, 120, 323, 3389, 0.50, 26.7, 765, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1295, '2025-05-21 09:39:12', 1, 4, 7.60, 0.60, 0.70, NULL, 114, 347, 3441, 0.10, 26.0, 733, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1296, '2025-05-20 11:37:12', 1, 4, 7.20, 0.60, 1.40, NULL, 94, 282, 3651, 0.50, 27.6, 667, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1297, '2025-05-19 11:36:12', 1, 4, 7.30, 1.60, 1.50, NULL, 93, 267, 3108, 1.00, 29.2, 777, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1298, '2025-05-18 11:58:12', 1, 4, 7.40, 2.80, 1.60, NULL, 119, 338, 3184, 0.30, 27.8, 684, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1299, '2025-05-17 09:47:12', 1, 4, 7.60, 1.00, 3.30, NULL, 101, 303, 3719, 0.80, 28.5, 684, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1300, '2025-05-16 11:09:12', 1, 4, 7.20, 0.90, 2.30, NULL, 120, 342, 3139, 0.10, 27.5, 788, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1301, '2025-05-15 11:39:12', 1, 4, 7.20, 2.80, 0.90, NULL, 98, 276, 3816, 0.00, 27.9, 782, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1302, '2025-05-14 09:03:12', 1, 4, 7.60, 1.70, 2.60, NULL, 117, 302, 3144, 0.50, 26.2, 793, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1303, '2025-05-13 09:21:12', 1, 4, 7.00, 2.60, 0.90, NULL, 103, 345, 3861, 0.90, 29.5, 759, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1304, '2025-05-12 11:11:12', 1, 4, 7.20, 2.30, 2.50, NULL, 102, 325, 3717, 0.10, 30.0, 697, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1305, '2025-05-11 08:57:12', 1, 4, 7.50, 1.70, 2.30, NULL, 83, 316, 3257, 0.90, 27.3, 793, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1306, '2025-05-10 10:58:12', 1, 4, 7.20, 3.00, 1.40, NULL, 111, 335, 3684, 1.00, 26.9, 669, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1307, '2025-05-09 10:27:12', 1, 4, 7.00, 2.60, 1.80, NULL, 105, 214, 3978, 0.20, 28.6, 717, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1308, '2025-05-08 11:10:12', 1, 4, 7.40, 0.50, 2.30, NULL, 95, 340, 3859, 0.50, 28.7, 778, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1309, '2025-05-07 09:19:12', 1, 4, 7.60, 2.70, 3.50, NULL, 99, 339, 3522, 0.20, 27.6, 661, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1310, '2025-05-06 09:15:12', 1, 4, 7.70, 2.10, 2.00, NULL, 97, 210, 3173, 0.20, 26.3, 799, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1311, '2025-05-05 10:41:12', 1, 4, 7.20, 2.40, 1.80, NULL, 84, 203, 3498, 0.80, 26.9, 691, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1312, '2025-05-04 10:26:12', 1, 4, 7.80, 0.60, 1.40, NULL, 89, 230, 3253, 0.10, 28.9, 744, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1313, '2025-05-03 09:33:12', 1, 4, 7.40, 2.30, 2.80, NULL, 101, 278, 3034, 0.40, 28.2, 668, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1314, '2025-05-02 10:36:12', 1, 4, 7.30, 0.80, 2.90, NULL, 83, 337, 3492, 1.00, 26.8, 800, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1315, '2025-05-01 11:00:12', 1, 4, 7.30, 1.40, 1.60, NULL, 100, 396, 3188, 0.50, 26.0, 741, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1316, '2025-04-30 09:17:12', 1, 4, 7.30, 2.40, 1.90, NULL, 109, 304, 3629, 0.90, 26.2, 728, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1317, '2025-04-29 08:49:12', 1, 4, 7.70, 0.80, 1.90, NULL, 114, 388, 3782, 1.00, 28.7, 711, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1318, '2025-04-28 11:12:12', 1, 4, 7.40, 2.90, 3.00, NULL, 116, 285, 3657, 0.90, 27.3, 765, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1319, '2025-04-27 11:23:12', 1, 4, 7.30, 1.40, 1.00, NULL, 95, 268, 3948, 0.00, 26.9, 778, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1320, '2025-04-26 10:53:12', 1, 4, 7.30, 2.00, 1.20, NULL, 95, 366, 3770, 0.30, 26.4, 703, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1321, '2025-04-25 11:43:12', 1, 4, 7.60, 0.70, 1.30, NULL, 108, 258, 3818, 0.70, 26.6, 655, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1322, '2025-04-24 09:53:12', 1, 4, 7.30, 2.40, 2.90, NULL, 94, 284, 3068, 0.10, 27.1, 770, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1323, '2025-04-23 11:33:12', 1, 4, 7.40, 1.70, 1.70, NULL, 116, 200, 3280, 0.60, 26.1, 724, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1324, '2025-04-22 08:19:12', 1, 4, 7.40, 2.20, 0.70, NULL, 98, 335, 3516, 0.20, 29.3, 714, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1325, '2025-04-21 08:35:12', 1, 4, 7.80, 0.60, 2.20, NULL, 111, 291, 3598, 0.50, 26.5, 751, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1326, '2025-04-20 10:03:12', 1, 4, 7.40, 1.20, 2.40, NULL, 92, 366, 3971, 0.70, 29.3, 745, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1327, '2025-04-19 10:14:12', 1, 4, 7.60, 1.10, 1.30, NULL, 116, 294, 3582, 0.20, 29.1, 752, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1328, '2025-04-18 09:45:12', 1, 4, 7.60, 1.60, 0.50, NULL, 98, 310, 3080, 0.50, 26.9, 755, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1329, '2025-04-17 09:05:12', 1, 4, 7.80, 2.40, 1.20, NULL, 107, 370, 3388, 0.40, 27.0, 761, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1330, '2025-04-16 11:46:12', 1, 4, 7.40, 2.90, 0.60, NULL, 85, 223, 3790, 1.00, 28.8, 719, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1331, '2025-04-15 11:32:12', 1, 4, 7.50, 1.70, 0.50, NULL, 99, 271, 3716, 1.00, 26.0, 735, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1332, '2025-04-14 11:46:12', 1, 4, 7.70, 0.50, 3.20, NULL, 86, 332, 3481, 0.10, 26.7, 694, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1333, '2025-04-13 11:56:12', 1, 4, 7.00, 1.70, 2.70, NULL, 92, 326, 3581, 0.80, 26.4, 657, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1334, '2025-04-12 08:33:12', 1, 4, 7.50, 0.60, 2.90, NULL, 87, 346, 3413, 0.80, 28.0, 791, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1335, '2025-04-11 08:36:12', 1, 4, 7.40, 1.10, 3.50, NULL, 120, 272, 3154, 0.20, 26.1, 761, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1336, '2025-04-10 10:40:12', 1, 4, 7.10, 0.60, 2.40, NULL, 85, 234, 3826, 0.00, 26.8, 697, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1337, '2025-04-09 10:39:12', 1, 4, 7.00, 2.00, 1.00, NULL, 83, 204, 3599, 0.20, 27.0, 676, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1338, '2025-04-08 11:25:12', 1, 4, 7.00, 2.20, 3.30, NULL, 90, 339, 3898, 0.30, 28.4, 743, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1339, '2025-04-07 08:16:12', 1, 4, 7.00, 1.00, 2.40, NULL, 90, 326, 3839, 0.00, 26.3, 714, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1340, '2025-04-06 08:21:12', 1, 4, 7.20, 1.30, 1.90, NULL, 94, 217, 3825, 0.90, 27.9, 722, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1341, '2025-04-05 09:26:12', 1, 4, 7.80, 1.00, 3.40, NULL, 81, 292, 3087, 0.00, 26.9, 667, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1342, '2025-04-04 10:38:12', 1, 4, 7.70, 1.00, 2.40, NULL, 106, 315, 3525, 0.50, 27.9, 678, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1343, '2025-04-03 10:21:12', 1, 4, 7.80, 1.10, 2.40, NULL, 107, 239, 3568, 0.60, 27.0, 744, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1344, '2025-04-02 08:05:12', 1, 4, 7.60, 0.90, 0.50, NULL, 116, 342, 3521, 0.00, 29.6, 705, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1345, '2025-04-01 11:06:12', 1, 4, 7.50, 3.00, 3.50, NULL, 80, 285, 3912, 0.60, 28.6, 778, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1346, '2025-03-31 08:51:12', 1, 4, 7.40, 2.20, 2.70, NULL, 116, 299, 3833, 1.00, 28.8, 784, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1347, '2025-03-30 08:52:12', 1, 4, 7.70, 3.00, 0.80, NULL, 108, 210, 3406, 0.10, 27.6, 785, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1348, '2025-03-29 08:53:12', 1, 4, 7.40, 0.50, 3.30, NULL, 94, 300, 3260, 0.80, 26.9, 774, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1349, '2025-03-28 11:45:12', 1, 4, 7.20, 1.80, 2.60, NULL, 95, 267, 3210, 1.00, 29.9, 698, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1350, '2025-03-27 11:17:12', 1, 4, 7.20, 2.50, 1.30, NULL, 88, 220, 3535, 0.30, 27.3, 798, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1351, '2025-03-26 09:04:12', 1, 4, 7.50, 1.90, 1.70, NULL, 104, 221, 3305, 0.30, 27.1, 730, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1352, '2025-03-25 11:09:12', 1, 4, 7.70, 2.70, 2.60, NULL, 119, 255, 3619, 0.90, 29.3, 684, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1353, '2025-03-24 08:44:12', 1, 4, 7.20, 0.70, 1.50, NULL, 119, 387, 3808, 0.00, 26.1, 784, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1354, '2025-03-23 11:29:12', 1, 4, 7.70, 2.80, 1.50, NULL, 87, 204, 3301, 0.80, 29.7, 798, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1355, '2025-03-22 11:36:12', 1, 4, 7.30, 2.90, 1.80, NULL, 106, 281, 3744, 0.60, 29.7, 675, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1356, '2025-03-21 09:28:12', 1, 4, 7.70, 1.30, 2.70, NULL, 106, 354, 3022, 0.00, 28.9, 742, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1357, '2025-03-20 09:27:12', 1, 4, 7.20, 1.70, 0.90, NULL, 108, 251, 3063, 0.00, 28.8, 776, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1358, '2025-03-19 08:24:12', 1, 4, 7.50, 0.70, 2.70, NULL, 92, 270, 3359, 0.70, 26.1, 728, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1359, '2025-03-18 09:52:12', 1, 4, 7.20, 2.40, 2.00, NULL, 115, 270, 3259, 0.40, 28.4, 769, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1360, '2025-03-17 10:32:12', 1, 4, 7.80, 1.00, 2.80, NULL, 114, 311, 3880, 0.70, 29.9, 704, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1361, '2025-03-16 09:35:12', 1, 4, 7.40, 2.80, 1.90, NULL, 80, 224, 3385, 0.40, 28.2, 663, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1362, '2025-03-15 09:58:12', 1, 4, 7.40, 2.30, 1.40, NULL, 101, 346, 3849, 0.90, 26.6, 790, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1363, '2025-03-14 11:59:12', 1, 4, 7.60, 2.80, 3.20, NULL, 116, 254, 3394, 0.60, 27.9, 695, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1424, '2025-01-12 10:32:12', 1, 4, 7.00, 1.50, 3.30, NULL, 91, 211, 3819, 0.00, 26.8, 674, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1364, '2025-03-13 08:23:12', 1, 4, 7.10, 2.80, 3.30, NULL, 102, 211, 3307, 0.10, 29.2, 749, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1365, '2025-03-12 10:53:12', 1, 4, 7.10, 2.80, 2.10, NULL, 108, 294, 3822, 0.10, 29.3, 653, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1366, '2025-03-11 09:06:12', 1, 4, 7.10, 0.90, 2.80, NULL, 97, 290, 3030, 0.10, 27.5, 755, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1367, '2025-03-10 09:28:12', 1, 4, 7.00, 1.60, 2.30, NULL, 98, 253, 3842, 0.20, 26.5, 783, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1368, '2025-03-09 10:14:12', 1, 4, 7.50, 1.80, 1.10, NULL, 99, 289, 3940, 0.00, 27.8, 694, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1369, '2025-03-08 09:54:12', 1, 4, 7.20, 2.00, 1.30, NULL, 109, 268, 3798, 0.10, 29.8, 685, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1370, '2025-03-07 11:40:12', 1, 4, 7.40, 2.60, 1.20, NULL, 92, 237, 3197, 0.50, 27.6, 790, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1371, '2025-03-06 08:08:12', 1, 4, 7.00, 0.50, 1.60, NULL, 89, 205, 3908, 0.30, 29.7, 753, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1372, '2025-03-05 08:24:12', 1, 4, 7.40, 0.60, 0.60, NULL, 114, 274, 3758, 0.90, 27.6, 659, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1373, '2025-03-04 09:35:12', 1, 4, 7.50, 2.80, 0.80, NULL, 108, 354, 3074, 0.70, 29.8, 670, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1374, '2025-03-03 10:30:12', 1, 4, 7.00, 1.60, 1.60, NULL, 106, 308, 3773, 0.80, 26.0, 759, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1375, '2025-03-02 10:07:12', 1, 4, 7.30, 1.20, 1.00, NULL, 80, 250, 3889, 0.30, 28.9, 662, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1376, '2025-03-01 10:30:12', 1, 4, 7.60, 1.80, 0.80, NULL, 98, 308, 3815, 0.70, 28.4, 755, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1377, '2025-02-28 11:30:12', 1, 4, 7.00, 2.20, 0.60, NULL, 86, 206, 3650, 0.50, 29.4, 783, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1378, '2025-02-27 09:48:12', 1, 4, 7.50, 2.00, 3.10, NULL, 89, 339, 3633, 0.70, 29.8, 688, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1379, '2025-02-26 11:49:12', 1, 4, 7.70, 1.90, 3.40, NULL, 99, 244, 3449, 0.90, 28.9, 703, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1380, '2025-02-25 10:15:12', 1, 4, 7.10, 0.60, 2.70, NULL, 110, 343, 3202, 0.50, 26.8, 761, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1381, '2025-02-24 08:08:12', 1, 4, 7.50, 0.60, 2.40, NULL, 88, 311, 3120, 1.00, 28.6, 736, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1382, '2025-02-23 10:45:12', 1, 4, 7.40, 2.10, 2.50, NULL, 110, 388, 3458, 0.20, 27.7, 728, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1383, '2025-02-22 09:57:12', 1, 4, 7.00, 1.30, 3.50, NULL, 106, 331, 3318, 0.80, 28.0, 714, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1384, '2025-02-21 09:20:12', 1, 4, 7.40, 0.50, 3.10, NULL, 84, 258, 3597, 0.70, 29.3, 738, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1385, '2025-02-20 09:48:12', 1, 4, 7.10, 1.00, 2.40, NULL, 98, 389, 3130, 0.40, 26.0, 682, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1386, '2025-02-19 11:35:12', 1, 4, 7.70, 2.50, 3.40, NULL, 116, 233, 3673, 0.30, 29.1, 754, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1387, '2025-02-18 10:58:12', 1, 4, 7.10, 1.50, 0.50, NULL, 104, 307, 3738, 0.60, 27.9, 666, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1388, '2025-02-17 11:57:12', 1, 4, 7.50, 1.80, 2.20, NULL, 116, 329, 3907, 0.40, 26.9, 800, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1389, '2025-02-16 11:04:12', 1, 4, 7.50, 0.70, 2.60, NULL, 84, 235, 3028, 0.90, 27.4, 793, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1390, '2025-02-15 11:53:12', 1, 4, 7.00, 2.10, 0.90, NULL, 94, 378, 3114, 0.20, 26.6, 687, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1391, '2025-02-14 08:38:12', 1, 4, 7.70, 1.40, 0.90, NULL, 97, 236, 3968, 0.70, 28.1, 780, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1392, '2025-02-13 09:24:12', 1, 4, 7.70, 1.10, 2.70, NULL, 80, 394, 3572, 1.00, 26.4, 760, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1393, '2025-02-12 08:33:12', 1, 4, 7.60, 3.00, 0.50, NULL, 111, 210, 3498, 0.20, 28.6, 797, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1394, '2025-02-11 10:07:12', 1, 4, 7.20, 1.20, 2.60, NULL, 97, 293, 3197, 1.00, 29.7, 771, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1395, '2025-02-10 09:12:12', 1, 4, 7.70, 3.00, 0.90, NULL, 100, 204, 3189, 0.10, 27.2, 769, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1396, '2025-02-09 11:23:12', 1, 4, 7.50, 1.60, 3.20, NULL, 112, 201, 3493, 0.00, 26.5, 766, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1397, '2025-02-08 09:04:12', 1, 4, 7.10, 2.50, 3.30, NULL, 99, 215, 3437, 0.20, 26.3, 789, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1398, '2025-02-07 09:57:12', 1, 4, 7.00, 2.40, 2.70, NULL, 96, 359, 3659, 0.40, 28.7, 707, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1399, '2025-02-06 09:10:12', 1, 4, 7.70, 1.90, 1.60, NULL, 101, 332, 3530, 0.70, 27.1, 794, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1400, '2025-02-05 10:59:12', 1, 4, 7.60, 0.80, 0.80, NULL, 92, 274, 3117, 0.90, 29.6, 770, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1401, '2025-02-04 10:24:12', 1, 4, 7.50, 1.10, 2.40, NULL, 92, 242, 3164, 0.80, 28.3, 787, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1402, '2025-02-03 11:01:12', 1, 4, 7.40, 1.30, 3.30, NULL, 86, 255, 3228, 0.40, 27.0, 722, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1403, '2025-02-02 10:45:12', 1, 4, 7.00, 0.90, 2.10, NULL, 96, 281, 3397, 0.40, 27.2, 693, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1404, '2025-02-01 10:02:12', 1, 4, 7.70, 1.20, 3.20, NULL, 106, 397, 3124, 0.40, 29.5, 735, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1405, '2025-01-31 08:35:12', 1, 4, 7.40, 2.50, 2.20, NULL, 100, 324, 3395, 0.10, 29.6, 720, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1406, '2025-01-30 10:51:12', 1, 4, 7.40, 2.30, 1.10, NULL, 114, 324, 3371, 0.20, 26.7, 659, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1407, '2025-01-29 09:41:12', 1, 4, 7.30, 1.70, 1.60, NULL, 95, 304, 3163, 0.10, 27.2, 770, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1408, '2025-01-28 10:03:12', 1, 4, 7.00, 1.70, 1.70, NULL, 83, 226, 3996, 0.70, 29.6, 756, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1409, '2025-01-27 08:39:12', 1, 4, 7.60, 1.20, 1.40, NULL, 84, 366, 3596, 0.70, 26.8, 658, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1410, '2025-01-26 09:36:12', 1, 4, 7.10, 1.90, 2.30, NULL, 107, 315, 3554, 0.00, 27.2, 797, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1411, '2025-01-25 09:54:12', 1, 4, 7.60, 1.50, 3.10, NULL, 118, 272, 3619, 0.60, 28.7, 709, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1412, '2025-01-24 11:08:12', 1, 4, 7.20, 0.70, 0.70, NULL, 89, 234, 3719, 0.10, 27.5, 716, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1413, '2025-01-23 08:06:12', 1, 4, 7.60, 1.60, 0.50, NULL, 108, 270, 3497, 0.20, 28.6, 655, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1414, '2025-01-22 10:57:12', 1, 4, 7.30, 1.40, 0.80, NULL, 81, 332, 3251, 0.80, 30.0, 793, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1415, '2025-01-21 09:03:12', 1, 4, 7.80, 2.30, 1.40, NULL, 85, 372, 3775, 0.00, 27.3, 722, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1416, '2025-01-20 11:50:12', 1, 4, 7.60, 1.70, 1.00, NULL, 91, 393, 3295, 0.90, 28.5, 772, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1417, '2025-01-19 08:35:12', 1, 4, 7.00, 2.40, 2.60, NULL, 97, 333, 3806, 1.00, 29.8, 651, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1418, '2025-01-18 11:44:12', 1, 4, 7.00, 0.80, 2.50, NULL, 120, 295, 3558, 1.00, 26.6, 769, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1419, '2025-01-17 09:42:12', 1, 4, 7.20, 1.00, 1.20, NULL, 95, 220, 3374, 0.00, 26.3, 780, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1420, '2025-01-16 09:09:12', 1, 4, 7.60, 2.60, 2.40, NULL, 80, 383, 3842, 0.80, 27.1, 699, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1421, '2025-01-15 10:18:12', 1, 4, 7.70, 2.30, 0.70, NULL, 116, 285, 3531, 0.30, 28.1, 721, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1422, '2025-01-14 11:56:12', 1, 4, 7.10, 1.80, 0.60, NULL, 115, 218, 3799, 0.80, 29.4, 744, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1423, '2025-01-13 08:33:12', 1, 4, 7.70, 2.30, 1.40, NULL, 99, 349, 3675, 0.60, 26.0, 722, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1425, '2025-01-11 09:26:12', 1, 4, 7.60, 0.80, 3.50, NULL, 113, 380, 3071, 0.80, 26.1, 751, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1426, '2025-01-10 10:04:12', 1, 4, 7.10, 0.60, 2.30, NULL, 87, 348, 3731, 0.00, 26.4, 721, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1427, '2025-01-09 09:14:12', 1, 4, 7.50, 0.90, 2.50, NULL, 81, 354, 3671, 0.30, 27.5, 725, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1428, '2025-01-08 09:33:12', 1, 4, 7.70, 1.10, 1.00, NULL, 103, 253, 3877, 0.10, 28.3, 695, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1429, '2025-01-07 08:31:12', 1, 4, 7.10, 1.70, 0.60, NULL, 107, 396, 3517, 0.20, 27.6, 787, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1430, '2025-01-06 09:25:12', 1, 4, 7.20, 2.00, 2.90, NULL, 88, 306, 3793, 0.50, 26.2, 738, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1431, '2025-01-05 10:01:12', 1, 4, 7.70, 3.00, 2.60, NULL, 111, 343, 3660, 0.40, 27.9, 761, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1432, '2025-01-04 11:02:12', 1, 4, 7.20, 0.70, 1.40, NULL, 112, 317, 3836, 0.20, 27.1, 783, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1433, '2025-01-03 09:23:12', 1, 4, 7.20, 1.30, 1.50, NULL, 111, 309, 3938, 0.80, 28.0, 661, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1434, '2025-01-02 08:55:12', 1, 4, 7.70, 0.70, 1.50, NULL, 86, 232, 3164, 0.10, 27.8, 721, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1435, '2025-01-01 09:45:12', 1, 4, 7.40, 1.00, 1.50, NULL, 83, 247, 3438, 0.00, 27.6, 797, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1436, '2024-12-31 10:39:12', 1, 4, 7.80, 2.70, 3.30, NULL, 83, 271, 3005, 0.70, 27.8, 678, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1437, '2024-12-30 10:02:12', 1, 4, 7.30, 1.00, 2.50, NULL, 110, 343, 3473, 0.30, 28.5, 739, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1438, '2024-12-29 10:31:12', 1, 4, 7.00, 0.80, 0.90, NULL, 91, 224, 3749, 0.10, 27.3, 734, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1439, '2024-12-28 08:31:12', 1, 4, 7.50, 3.00, 1.40, NULL, 117, 232, 3216, 0.40, 27.2, 667, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1440, '2024-12-27 10:16:12', 1, 4, 7.30, 3.00, 1.40, NULL, 112, 206, 3792, 0.40, 27.5, 761, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1441, '2024-12-26 09:14:12', 1, 4, 7.40, 2.30, 2.90, NULL, 114, 265, 3468, 1.00, 27.8, 759, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1442, '2024-12-25 10:25:12', 1, 4, 7.50, 2.80, 3.00, NULL, 108, 341, 3905, 0.10, 27.3, 719, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1443, '2024-12-24 08:02:12', 1, 4, 7.80, 2.10, 1.60, NULL, 99, 282, 3310, 0.60, 29.7, 764, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1444, '2024-12-23 09:13:12', 1, 4, 7.10, 1.80, 2.10, NULL, 120, 236, 3318, 0.20, 27.5, 784, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1445, '2024-12-22 11:46:12', 1, 4, 7.80, 2.70, 2.50, NULL, 90, 261, 3379, 0.90, 26.3, 763, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1446, '2024-12-21 10:30:12', 1, 4, 7.60, 2.20, 2.60, NULL, 116, 354, 3543, 0.30, 27.9, 670, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1447, '2024-12-20 09:30:12', 1, 4, 7.30, 1.20, 1.00, NULL, 98, 220, 3262, 0.90, 27.2, 651, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1448, '2024-12-19 08:27:12', 1, 4, 7.70, 1.90, 1.30, NULL, 87, 333, 3356, 0.20, 27.9, 761, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1449, '2024-12-18 11:00:12', 1, 4, 7.10, 2.30, 1.10, NULL, 108, 392, 3348, 0.70, 29.6, 669, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1450, '2024-12-17 09:22:12', 1, 4, 7.50, 2.60, 1.60, NULL, 116, 335, 3124, 0.30, 27.3, 703, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1451, '2024-12-16 09:24:12', 1, 4, 7.80, 2.00, 2.90, NULL, 81, 256, 3537, 0.20, 29.6, 748, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1452, '2024-12-15 09:30:12', 1, 4, 7.00, 1.60, 2.90, NULL, 98, 379, 3057, 0.60, 28.2, 744, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1453, '2024-12-14 11:47:12', 1, 4, 7.50, 2.80, 3.00, NULL, 110, 346, 3082, 0.00, 27.0, 726, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1454, '2024-12-13 08:26:12', 1, 4, 7.00, 0.50, 3.00, NULL, 98, 344, 3553, 0.10, 29.7, 773, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1455, '2024-12-12 09:30:12', 1, 4, 7.40, 1.40, 3.10, NULL, 109, 382, 3191, 0.20, 29.3, 769, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1456, '2024-12-11 11:28:12', 1, 4, 7.40, 0.90, 2.20, NULL, 82, 240, 3087, 0.10, 26.3, 672, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1457, '2024-12-10 11:10:12', 1, 4, 7.40, 0.90, 3.00, NULL, 109, 267, 3975, 0.40, 30.0, 780, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1458, '2024-12-09 08:21:12', 1, 4, 7.20, 2.30, 0.90, NULL, 90, 368, 3467, 0.00, 27.6, 730, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1459, '2024-12-08 09:09:12', 1, 4, 7.70, 1.60, 0.70, NULL, 116, 249, 3051, 0.50, 28.7, 799, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1460, '2024-12-07 09:55:12', 1, 4, 7.00, 1.80, 2.90, NULL, 95, 282, 3323, 0.70, 27.3, 671, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1461, '2025-12-06 09:38:12', 1, 6, 7.80, 0.80, 2.40, NULL, 98, 385, 3928, 0.30, 27.0, 703, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1462, '2025-12-05 11:19:12', 1, 6, 7.00, 2.70, 1.20, NULL, 89, 302, 3202, 0.30, 29.8, 742, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1463, '2025-12-04 10:36:12', 1, 6, 7.60, 2.70, 2.60, NULL, 102, 316, 3213, 1.00, 27.2, 661, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1464, '2025-12-03 09:05:12', 1, 6, 7.40, 0.90, 1.00, NULL, 95, 325, 3713, 0.00, 30.0, 716, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1465, '2025-12-02 11:27:12', 1, 6, 7.20, 3.00, 2.80, NULL, 119, 236, 3215, 0.10, 27.2, 669, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1466, '2025-12-01 10:22:12', 1, 6, 7.80, 2.20, 0.90, NULL, 106, 213, 3526, 0.30, 28.7, 664, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1467, '2025-11-30 09:09:12', 1, 6, 7.10, 0.60, 2.40, NULL, 82, 322, 3093, 0.50, 29.4, 756, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1468, '2025-11-29 11:06:12', 1, 6, 7.00, 1.80, 2.50, NULL, 89, 367, 3615, 0.50, 27.2, 707, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1469, '2025-11-28 08:30:12', 1, 6, 7.00, 1.70, 1.30, NULL, 104, 222, 3459, 0.00, 29.7, 659, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1470, '2025-11-27 08:09:12', 1, 6, 7.20, 2.10, 1.40, NULL, 95, 378, 3078, 0.90, 26.9, 777, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1471, '2025-11-26 09:10:12', 1, 6, 7.20, 0.80, 2.30, NULL, 85, 295, 3725, 0.00, 29.6, 695, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1472, '2025-11-25 08:35:12', 1, 6, 7.10, 1.90, 0.70, NULL, 119, 313, 3437, 0.90, 29.4, 781, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1473, '2025-11-24 10:57:12', 1, 6, 7.20, 1.80, 3.40, NULL, 94, 286, 3589, 0.10, 27.2, 705, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1474, '2025-11-23 11:36:12', 1, 6, 7.20, 2.80, 0.90, NULL, 86, 291, 3412, 0.70, 26.0, 759, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1475, '2025-11-22 10:53:12', 1, 6, 7.70, 2.50, 1.40, NULL, 111, 391, 3608, 1.00, 27.3, 665, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1476, '2025-11-21 10:16:12', 1, 6, 7.60, 0.80, 3.30, NULL, 80, 233, 3472, 0.90, 26.6, 676, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1477, '2025-11-20 11:54:12', 1, 6, 7.10, 1.10, 3.30, NULL, 95, 336, 3724, 0.60, 27.8, 652, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1478, '2025-11-19 08:55:12', 1, 6, 7.70, 1.80, 2.10, NULL, 117, 384, 3211, 0.00, 28.4, 761, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1479, '2025-11-18 09:12:12', 1, 6, 7.70, 1.40, 2.00, NULL, 99, 366, 3172, 0.80, 27.4, 663, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1480, '2025-11-17 09:47:12', 1, 6, 7.10, 0.80, 1.90, NULL, 110, 378, 3011, 0.20, 29.2, 774, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1481, '2025-11-16 09:44:12', 1, 6, 7.10, 1.00, 1.30, NULL, 88, 319, 3936, 0.60, 26.0, 772, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1482, '2025-11-15 11:20:12', 1, 6, 7.00, 2.30, 1.90, NULL, 117, 348, 3566, 0.00, 29.5, 670, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1483, '2025-11-14 10:42:12', 1, 6, 7.60, 0.90, 2.90, NULL, 91, 284, 3928, 0.20, 26.0, 659, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1484, '2025-11-13 08:03:12', 1, 6, 7.80, 1.30, 1.20, NULL, 91, 215, 3312, 0.70, 29.8, 759, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1485, '2025-11-12 08:55:12', 1, 6, 7.40, 1.30, 0.60, NULL, 89, 358, 3700, 0.20, 29.4, 666, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1486, '2025-11-11 08:29:12', 1, 6, 7.00, 1.80, 2.30, NULL, 102, 255, 3736, 0.60, 29.3, 782, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1487, '2025-11-10 08:50:12', 1, 6, 7.80, 1.50, 1.00, NULL, 119, 383, 3486, 0.20, 29.8, 757, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1488, '2025-11-09 11:40:12', 1, 6, 7.40, 2.00, 3.50, NULL, 100, 297, 3351, 0.60, 27.3, 715, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1489, '2025-11-08 09:19:12', 1, 6, 7.10, 2.40, 1.50, NULL, 117, 391, 3172, 0.40, 26.9, 658, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1490, '2025-11-07 11:50:12', 1, 6, 7.20, 2.80, 0.60, NULL, 117, 231, 3028, 0.10, 28.3, 795, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1491, '2025-11-06 10:12:12', 1, 6, 7.00, 2.60, 0.50, NULL, 91, 224, 3211, 0.60, 29.2, 761, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1492, '2025-11-05 11:56:12', 1, 6, 7.80, 1.50, 3.30, NULL, 90, 246, 3425, 0.10, 28.2, 776, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1493, '2025-11-04 09:03:12', 1, 6, 7.10, 2.30, 2.20, NULL, 109, 213, 3531, 0.20, 28.9, 659, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1494, '2025-11-03 10:48:12', 1, 6, 7.70, 2.70, 2.00, NULL, 102, 312, 3942, 0.30, 29.3, 786, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1495, '2025-11-02 09:28:12', 1, 6, 7.50, 1.80, 2.70, NULL, 106, 318, 3977, 1.00, 27.8, 732, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1496, '2025-11-01 09:09:12', 1, 6, 7.20, 2.00, 0.50, NULL, 87, 386, 3333, 0.20, 26.6, 759, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1497, '2025-10-31 09:53:12', 1, 6, 7.80, 1.70, 1.90, NULL, 94, 278, 3953, 0.40, 27.5, 758, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1498, '2025-10-30 11:21:12', 1, 6, 7.50, 1.20, 0.70, NULL, 107, 332, 3424, 0.20, 26.0, 682, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1499, '2025-10-29 11:59:12', 1, 6, 7.40, 1.60, 1.60, NULL, 104, 297, 3420, 0.00, 29.0, 706, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1500, '2025-10-28 08:41:12', 1, 6, 7.60, 2.10, 1.60, NULL, 90, 370, 3647, 0.50, 26.0, 673, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1501, '2025-10-27 11:44:12', 1, 6, 7.40, 1.10, 1.70, NULL, 105, 347, 3578, 0.80, 28.7, 652, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1502, '2025-10-26 08:45:12', 1, 6, 7.40, 2.40, 1.80, NULL, 118, 317, 3084, 0.80, 28.8, 678, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1503, '2025-10-25 11:41:12', 1, 6, 7.40, 1.80, 2.20, NULL, 103, 355, 3900, 0.20, 29.7, 739, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1504, '2025-10-24 09:33:12', 1, 6, 7.10, 1.30, 3.20, NULL, 116, 359, 3922, 0.60, 28.4, 720, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1505, '2025-10-23 10:45:12', 1, 6, 7.80, 1.60, 1.50, NULL, 113, 346, 3689, 0.70, 29.6, 710, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1506, '2025-10-22 09:52:12', 1, 6, 7.50, 2.70, 2.30, NULL, 81, 256, 3364, 0.20, 26.9, 791, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1507, '2025-10-21 09:11:12', 1, 6, 7.60, 0.90, 2.30, NULL, 113, 362, 3771, 0.10, 29.1, 754, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1508, '2025-10-20 09:16:12', 1, 6, 7.20, 0.50, 3.50, NULL, 117, 285, 3003, 0.20, 29.4, 662, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1509, '2025-10-19 09:38:12', 1, 6, 7.40, 1.60, 3.40, NULL, 82, 292, 3440, 0.10, 26.4, 783, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1510, '2025-10-18 10:08:12', 1, 6, 7.50, 2.40, 1.00, NULL, 119, 300, 3031, 0.60, 27.9, 783, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1511, '2025-10-17 11:04:12', 1, 6, 7.40, 1.00, 2.30, NULL, 114, 344, 3971, 0.20, 29.5, 741, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1512, '2025-10-16 08:11:12', 1, 6, 7.60, 2.60, 2.90, NULL, 100, 341, 3895, 0.70, 27.5, 781, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1513, '2025-10-15 08:17:12', 1, 6, 7.60, 1.20, 0.90, NULL, 89, 356, 3107, 0.20, 29.3, 761, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1514, '2025-10-14 08:30:12', 1, 6, 7.80, 2.60, 1.20, NULL, 106, 335, 3904, 0.10, 28.7, 692, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1515, '2025-10-13 11:51:12', 1, 6, 7.10, 1.80, 0.60, NULL, 101, 330, 3332, 0.00, 27.5, 723, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1516, '2025-10-12 11:06:12', 1, 6, 7.30, 0.90, 2.00, NULL, 95, 235, 3304, 0.70, 29.3, 751, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1517, '2025-10-11 11:37:12', 1, 6, 7.60, 0.50, 1.00, NULL, 102, 300, 3775, 0.20, 28.2, 785, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1518, '2025-10-10 11:11:12', 1, 6, 7.40, 3.00, 0.80, NULL, 113, 382, 3179, 0.80, 29.0, 654, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1519, '2025-10-09 08:52:12', 1, 6, 7.70, 0.60, 2.50, NULL, 120, 259, 3919, 0.20, 26.3, 778, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1520, '2025-10-08 09:24:12', 1, 6, 7.10, 2.60, 2.80, NULL, 93, 398, 3792, 0.90, 26.6, 666, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1521, '2025-10-07 10:40:12', 1, 6, 7.70, 2.70, 0.70, NULL, 86, 356, 3783, 0.30, 28.8, 670, 'Routine monthly check', '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1522, '2025-10-06 11:10:12', 1, 6, 7.50, 2.30, 1.90, NULL, 93, 304, 3140, 0.40, 28.2, 669, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1523, '2025-10-05 10:39:12', 1, 6, 7.00, 2.00, 2.60, NULL, 112, 221, 3477, 0.70, 27.8, 794, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1524, '2025-10-04 10:10:12', 1, 6, 7.70, 1.70, 2.50, NULL, 113, 375, 3480, 0.00, 26.0, 744, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1525, '2025-10-03 10:35:12', 1, 6, 7.40, 2.70, 1.70, NULL, 112, 311, 3282, 0.60, 26.5, 741, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1526, '2025-10-02 08:10:12', 1, 6, 7.00, 2.90, 1.60, NULL, 100, 299, 3727, 0.80, 29.3, 755, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1527, '2025-10-01 09:46:12', 1, 6, 7.20, 1.30, 1.40, NULL, 85, 297, 3370, 0.40, 27.6, 671, NULL, '2025-12-06 12:13:12', '2025-12-06 12:13:12');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1528, '2025-09-30 08:47:12', 1, 6, 7.80, 2.20, 2.20, NULL, 117, 321, 3692, 0.00, 29.5, 792, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1529, '2025-09-29 09:17:13', 1, 6, 7.10, 2.10, 1.40, NULL, 101, 238, 3200, 0.80, 26.9, 749, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1530, '2025-09-28 10:13:13', 1, 6, 7.60, 0.60, 2.00, NULL, 90, 211, 3547, 0.60, 27.0, 713, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1531, '2025-09-27 09:26:13', 1, 6, 7.30, 3.00, 1.70, NULL, 90, 217, 3773, 0.80, 28.7, 657, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1532, '2025-09-26 10:36:13', 1, 6, 7.00, 2.30, 1.00, NULL, 113, 280, 3713, 0.50, 26.7, 782, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1533, '2025-09-25 09:44:13', 1, 6, 7.00, 1.30, 0.70, NULL, 92, 391, 3958, 0.10, 27.2, 728, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1534, '2025-09-24 10:27:13', 1, 6, 7.10, 2.50, 2.60, NULL, 87, 328, 3545, 0.80, 28.5, 680, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1535, '2025-09-23 08:53:13', 1, 6, 7.00, 1.00, 0.80, NULL, 103, 321, 3635, 0.00, 29.0, 704, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1536, '2025-09-22 11:57:13', 1, 6, 7.50, 2.90, 2.40, NULL, 104, 325, 3628, 0.30, 26.2, 770, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1537, '2025-09-21 11:28:13', 1, 6, 7.30, 1.90, 2.70, NULL, 87, 243, 3733, 0.80, 28.6, 758, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1538, '2025-09-20 08:23:13', 1, 6, 7.30, 0.90, 2.20, NULL, 80, 303, 3814, 0.90, 29.5, 797, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1539, '2025-09-19 09:23:13', 1, 6, 7.40, 2.40, 3.40, NULL, 101, 241, 3270, 0.40, 26.1, 771, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1540, '2025-09-18 08:31:13', 1, 6, 7.50, 2.00, 0.70, NULL, 105, 300, 3035, 0.30, 26.9, 775, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1541, '2025-09-17 10:40:13', 1, 6, 7.50, 1.50, 1.10, NULL, 98, 396, 3465, 0.30, 27.5, 650, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1542, '2025-09-16 08:23:13', 1, 6, 7.30, 1.70, 1.90, NULL, 86, 362, 3559, 0.40, 28.0, 782, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1543, '2025-09-15 11:07:13', 1, 6, 7.60, 0.60, 0.50, NULL, 111, 326, 3874, 0.80, 28.1, 714, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1544, '2025-09-14 09:56:13', 1, 6, 7.70, 2.90, 0.50, NULL, 112, 209, 3337, 0.40, 27.3, 652, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1545, '2025-09-13 11:49:13', 1, 6, 7.00, 1.20, 2.20, NULL, 116, 341, 3957, 0.00, 26.4, 690, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1546, '2025-09-12 10:46:13', 1, 6, 7.50, 2.80, 2.80, NULL, 105, 218, 3313, 0.10, 29.2, 670, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1547, '2025-09-11 10:58:13', 1, 6, 7.50, 2.30, 2.90, NULL, 94, 201, 3877, 0.40, 26.4, 749, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1548, '2025-09-10 11:49:13', 1, 6, 7.40, 2.70, 1.90, NULL, 97, 308, 3187, 0.60, 26.0, 665, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1549, '2025-09-09 09:19:13', 1, 6, 7.20, 0.50, 1.40, NULL, 118, 385, 3729, 0.30, 28.8, 680, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1550, '2025-09-08 08:09:13', 1, 6, 7.70, 2.10, 1.50, NULL, 118, 282, 3522, 0.30, 29.1, 738, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1551, '2025-09-07 11:46:13', 1, 6, 7.70, 2.60, 2.40, NULL, 97, 294, 3868, 1.00, 28.4, 740, 'Routine monthly check', '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1552, '2025-09-06 09:23:13', 1, 6, 7.50, 2.40, 1.60, NULL, 96, 311, 3109, 0.90, 29.8, 677, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1553, '2025-09-05 11:44:13', 1, 6, 7.60, 1.40, 2.70, NULL, 105, 254, 3734, 0.80, 27.0, 755, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1554, '2025-09-04 11:18:13', 1, 6, 7.80, 0.80, 1.60, NULL, 83, 302, 3608, 0.20, 27.8, 744, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1555, '2025-09-03 08:43:13', 1, 6, 7.00, 2.40, 2.90, NULL, 90, 256, 3222, 0.80, 26.5, 670, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1556, '2025-09-02 09:52:13', 1, 6, 7.30, 2.20, 2.20, NULL, 118, 221, 3755, 0.80, 26.9, 721, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1557, '2025-09-01 08:40:13', 1, 6, 7.40, 2.00, 0.90, NULL, 91, 354, 3602, 1.00, 28.1, 775, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1558, '2025-08-31 08:59:13', 1, 6, 7.40, 1.20, 3.20, NULL, 85, 215, 3993, 0.20, 27.1, 785, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1559, '2025-08-30 11:57:13', 1, 6, 7.60, 0.50, 1.80, NULL, 101, 244, 3000, 0.90, 28.9, 673, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1560, '2025-08-29 10:52:13', 1, 6, 7.30, 1.30, 2.30, NULL, 86, 292, 3623, 0.00, 27.1, 707, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1561, '2025-08-28 10:53:13', 1, 6, 7.10, 2.90, 0.60, NULL, 101, 236, 3813, 0.40, 26.3, 702, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1562, '2025-08-27 09:54:13', 1, 6, 7.70, 0.80, 2.40, NULL, 118, 287, 3389, 0.30, 29.1, 728, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1563, '2025-08-26 09:30:13', 1, 6, 7.10, 0.50, 3.10, NULL, 105, 323, 3516, 0.20, 27.1, 776, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1564, '2025-08-25 08:13:13', 1, 6, 7.50, 2.20, 1.80, NULL, 118, 327, 3714, 1.00, 27.8, 701, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1565, '2025-08-24 09:20:13', 1, 6, 7.30, 1.40, 2.50, NULL, 99, 275, 3732, 0.20, 27.7, 669, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1566, '2025-08-23 11:38:13', 1, 6, 7.80, 2.40, 1.40, NULL, 107, 339, 3636, 0.10, 29.1, 710, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1567, '2025-08-22 11:15:13', 1, 6, 7.30, 1.80, 2.30, NULL, 103, 400, 3396, 0.90, 26.1, 708, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1568, '2025-08-21 10:44:13', 1, 6, 7.00, 0.60, 1.20, NULL, 100, 363, 3465, 0.20, 29.2, 798, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1569, '2025-08-20 10:45:13', 1, 6, 7.80, 1.10, 2.10, NULL, 117, 309, 3250, 0.20, 29.7, 774, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1570, '2025-08-19 10:15:13', 1, 6, 7.80, 0.50, 1.70, NULL, 91, 257, 3624, 0.80, 29.5, 782, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1571, '2025-08-18 08:41:13', 1, 6, 7.30, 1.40, 2.60, NULL, 120, 387, 3490, 0.60, 28.8, 711, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1572, '2025-08-17 08:18:13', 1, 6, 7.40, 2.80, 2.30, NULL, 107, 207, 3967, 0.00, 26.9, 782, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1573, '2025-08-16 10:01:13', 1, 6, 7.40, 2.20, 1.30, NULL, 103, 284, 3446, 0.20, 28.2, 761, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1574, '2025-08-15 10:29:13', 1, 6, 7.60, 0.50, 2.30, NULL, 98, 270, 3281, 0.00, 29.6, 765, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1575, '2025-08-14 11:48:13', 1, 6, 7.30, 0.50, 2.60, NULL, 109, 270, 3572, 0.80, 27.8, 777, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1576, '2025-08-13 09:58:13', 1, 6, 7.30, 2.90, 1.60, NULL, 105, 315, 3043, 0.60, 28.6, 705, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1577, '2025-08-12 10:57:13', 1, 6, 7.40, 2.80, 3.30, NULL, 98, 374, 3195, 0.60, 27.4, 653, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1578, '2025-08-11 09:04:13', 1, 6, 7.10, 1.10, 3.30, NULL, 105, 337, 3168, 0.60, 29.8, 650, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1579, '2025-08-10 08:50:13', 1, 6, 7.60, 2.00, 0.80, NULL, 87, 265, 3008, 0.00, 27.1, 723, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1580, '2025-08-09 08:28:13', 1, 6, 7.50, 2.00, 1.30, NULL, 113, 265, 3495, 0.70, 26.6, 799, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1581, '2025-08-08 10:05:13', 1, 6, 7.00, 2.60, 1.30, NULL, 104, 272, 3084, 0.20, 28.3, 663, 'Routine monthly check', '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1582, '2025-08-07 08:58:13', 1, 6, 7.00, 2.30, 2.10, NULL, 94, 286, 3891, 0.70, 26.1, 739, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1583, '2025-08-06 09:57:13', 1, 6, 7.10, 2.90, 2.30, NULL, 97, 331, 3513, 0.20, 29.6, 790, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1584, '2025-08-05 08:23:13', 1, 6, 7.30, 2.80, 2.60, NULL, 115, 315, 3272, 0.90, 26.6, 749, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1585, '2025-08-04 10:21:13', 1, 6, 7.30, 2.30, 0.80, NULL, 112, 332, 3803, 0.10, 27.4, 705, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1586, '2025-08-03 11:38:13', 1, 6, 7.60, 1.00, 1.30, NULL, 90, 371, 3219, 0.10, 27.6, 712, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1587, '2025-08-02 10:21:13', 1, 6, 7.20, 1.90, 0.50, NULL, 89, 320, 3359, 0.60, 26.1, 660, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1588, '2025-08-01 08:33:13', 1, 6, 7.10, 2.30, 1.00, NULL, 92, 246, 3967, 0.60, 28.2, 775, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1589, '2025-07-31 10:35:13', 1, 6, 7.30, 1.70, 3.40, NULL, 114, 309, 3996, 0.20, 28.4, 679, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1590, '2025-07-30 09:10:13', 1, 6, 7.40, 0.50, 2.70, NULL, 86, 288, 3937, 0.40, 28.0, 765, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1591, '2025-07-29 11:57:13', 1, 6, 7.70, 0.70, 2.30, NULL, 103, 371, 3215, 0.20, 26.8, 652, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1592, '2025-07-28 11:27:13', 1, 6, 7.20, 2.40, 3.00, NULL, 97, 227, 3624, 1.00, 30.0, 680, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1593, '2025-07-27 09:09:13', 1, 6, 7.50, 1.50, 0.90, NULL, 82, 368, 3508, 0.00, 26.7, 786, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1594, '2025-07-26 11:48:13', 1, 6, 7.70, 0.50, 1.60, NULL, 97, 304, 3555, 0.10, 26.1, 705, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1595, '2025-07-25 10:38:13', 1, 6, 7.00, 0.70, 0.70, NULL, 91, 254, 3625, 0.50, 30.0, 721, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1596, '2025-07-24 08:18:13', 1, 6, 7.50, 1.70, 3.30, NULL, 87, 273, 3773, 0.30, 26.5, 677, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1597, '2025-07-23 08:32:13', 1, 6, 7.50, 1.10, 2.70, NULL, 101, 302, 3391, 0.30, 29.8, 697, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1598, '2025-07-22 09:21:13', 1, 6, 7.80, 1.80, 3.50, NULL, 81, 368, 3502, 0.00, 27.5, 706, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1599, '2025-07-21 08:54:13', 1, 6, 7.30, 1.90, 3.50, NULL, 94, 353, 3241, 0.80, 27.6, 766, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1600, '2025-07-20 10:41:13', 1, 6, 7.00, 1.90, 1.20, NULL, 86, 291, 3745, 0.00, 26.6, 771, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1601, '2025-07-19 10:30:13', 1, 6, 7.70, 1.50, 0.60, NULL, 103, 284, 3338, 0.00, 27.2, 673, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1602, '2025-07-18 08:22:13', 1, 6, 7.70, 1.40, 3.40, NULL, 88, 301, 3531, 0.90, 29.7, 796, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1603, '2025-07-17 08:47:13', 1, 6, 7.80, 1.50, 0.80, NULL, 105, 356, 3530, 0.30, 29.2, 692, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1604, '2025-07-16 08:55:13', 1, 6, 7.00, 1.80, 0.80, NULL, 113, 339, 3750, 0.90, 28.2, 713, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1605, '2025-07-15 09:43:13', 1, 6, 7.70, 3.00, 2.40, NULL, 111, 326, 3422, 0.80, 26.5, 704, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1606, '2025-07-14 08:10:13', 1, 6, 7.00, 0.70, 2.20, NULL, 86, 288, 3535, 0.50, 27.1, 718, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1607, '2025-07-13 10:18:13', 1, 6, 7.40, 0.60, 1.00, NULL, 86, 309, 3938, 0.90, 26.5, 788, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1608, '2025-07-12 08:57:13', 1, 6, 7.20, 1.60, 1.80, NULL, 102, 322, 3093, 0.40, 26.3, 753, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1609, '2025-07-11 09:26:13', 1, 6, 7.70, 0.80, 2.60, NULL, 110, 232, 3548, 0.30, 28.4, 753, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1610, '2025-07-10 09:11:13', 1, 6, 7.00, 2.30, 2.80, NULL, 108, 326, 3998, 0.50, 30.0, 677, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1611, '2025-07-09 10:23:13', 1, 6, 7.00, 2.00, 2.10, NULL, 83, 350, 3058, 0.30, 28.9, 794, 'Routine monthly check', '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1612, '2025-07-08 10:50:13', 1, 6, 7.20, 1.80, 1.70, NULL, 109, 216, 3964, 0.10, 27.9, 729, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1613, '2025-07-07 08:13:13', 1, 6, 7.00, 1.30, 2.00, NULL, 85, 246, 3387, 0.10, 27.4, 699, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1614, '2025-07-06 08:20:13', 1, 6, 7.80, 2.60, 3.50, NULL, 101, 319, 3651, 0.10, 28.4, 759, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1615, '2025-07-05 08:18:13', 1, 6, 7.10, 1.30, 2.00, NULL, 108, 276, 3395, 0.90, 26.9, 674, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1616, '2025-07-04 08:09:13', 1, 6, 7.10, 2.10, 2.70, NULL, 99, 360, 3790, 0.60, 26.1, 725, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1617, '2025-07-03 11:15:13', 1, 6, 7.00, 2.30, 2.80, NULL, 89, 347, 3831, 0.90, 29.6, 726, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1618, '2025-07-02 11:56:13', 1, 6, 7.00, 1.00, 1.70, NULL, 98, 228, 3202, 0.90, 27.6, 793, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1619, '2025-07-01 10:42:13', 1, 6, 7.00, 1.70, 0.70, NULL, 96, 381, 3894, 0.10, 29.3, 659, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1620, '2025-06-30 09:51:13', 1, 6, 7.80, 0.80, 1.00, NULL, 100, 272, 3643, 0.40, 26.4, 787, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1621, '2025-06-29 08:07:13', 1, 6, 7.40, 2.30, 2.10, NULL, 114, 223, 3361, 0.70, 26.4, 695, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1622, '2025-06-28 08:52:13', 1, 6, 7.80, 3.00, 1.10, NULL, 113, 284, 3235, 0.00, 28.4, 800, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1623, '2025-06-27 09:40:13', 1, 6, 7.00, 1.00, 2.60, NULL, 118, 228, 3228, 0.40, 26.4, 652, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1624, '2025-06-26 10:05:13', 1, 6, 7.70, 0.90, 2.00, NULL, 99, 265, 3694, 0.00, 28.5, 740, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1625, '2025-06-25 10:48:13', 1, 6, 7.50, 1.40, 2.50, NULL, 115, 306, 3484, 0.10, 26.1, 688, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1626, '2025-06-24 10:06:13', 1, 6, 7.60, 1.50, 3.20, NULL, 117, 374, 3205, 0.80, 30.0, 679, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1627, '2025-06-23 08:41:13', 1, 6, 7.70, 2.90, 1.40, NULL, 104, 254, 3811, 0.00, 27.7, 671, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1628, '2025-06-22 11:43:13', 1, 6, 7.50, 0.80, 3.20, NULL, 120, 379, 3204, 0.80, 26.3, 708, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1629, '2025-06-21 11:04:13', 1, 6, 7.10, 2.60, 0.50, NULL, 83, 336, 3175, 0.00, 27.9, 658, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1630, '2025-06-20 11:31:13', 1, 6, 7.50, 0.60, 1.60, NULL, 91, 386, 3978, 0.20, 28.0, 679, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1631, '2025-06-19 11:31:13', 1, 6, 7.60, 2.70, 1.20, NULL, 108, 352, 3221, 0.00, 28.7, 721, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1632, '2025-06-18 08:21:13', 1, 6, 7.50, 2.40, 1.70, NULL, 111, 290, 3957, 0.90, 26.8, 798, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1633, '2025-06-17 11:52:13', 1, 6, 7.60, 1.00, 1.50, NULL, 84, 304, 3141, 0.90, 26.9, 716, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1634, '2025-06-16 09:17:13', 1, 6, 7.40, 1.60, 2.10, NULL, 92, 302, 3593, 0.40, 28.0, 712, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1635, '2025-06-15 11:46:13', 1, 6, 7.80, 2.80, 3.20, NULL, 86, 388, 3367, 0.50, 26.5, 764, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1636, '2025-06-14 08:22:13', 1, 6, 7.10, 0.90, 1.70, NULL, 91, 256, 3240, 0.00, 26.5, 738, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1637, '2025-06-13 10:24:13', 1, 6, 7.70, 2.00, 1.10, NULL, 84, 304, 3955, 0.80, 28.5, 703, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1638, '2025-06-12 08:40:13', 1, 6, 7.10, 2.00, 3.50, NULL, 99, 282, 3818, 0.50, 26.8, 706, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1639, '2025-06-11 10:22:13', 1, 6, 7.50, 1.70, 2.90, NULL, 113, 203, 3665, 0.50, 29.5, 794, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1640, '2025-06-10 08:04:13', 1, 6, 7.40, 2.00, 2.80, NULL, 108, 367, 3371, 0.10, 27.0, 753, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1641, '2025-06-09 11:11:13', 1, 6, 7.80, 2.30, 1.50, NULL, 108, 377, 3672, 0.70, 27.0, 745, 'Routine monthly check', '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1642, '2025-06-08 08:49:13', 1, 6, 7.20, 0.50, 0.70, NULL, 120, 230, 3796, 1.00, 27.1, 782, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1643, '2025-06-07 08:37:13', 1, 6, 7.40, 2.30, 2.30, NULL, 113, 242, 3769, 0.80, 26.0, 739, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1644, '2025-06-06 10:35:13', 1, 6, 7.70, 0.60, 0.70, NULL, 119, 309, 3818, 0.00, 29.5, 665, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1645, '2025-06-05 10:12:13', 1, 6, 7.00, 2.30, 1.30, NULL, 97, 354, 3857, 0.60, 28.3, 728, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1646, '2025-06-04 11:22:13', 1, 6, 7.30, 2.40, 1.70, NULL, 91, 344, 3167, 0.90, 28.1, 664, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1647, '2025-06-03 08:24:13', 1, 6, 7.00, 1.40, 2.10, NULL, 99, 229, 3209, 0.40, 30.0, 753, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1648, '2025-06-02 08:42:13', 1, 6, 7.20, 2.20, 2.50, NULL, 120, 289, 3741, 0.30, 26.4, 764, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1649, '2025-06-01 10:17:13', 1, 6, 7.10, 0.50, 2.60, NULL, 105, 364, 3550, 0.30, 28.5, 789, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1650, '2025-05-31 10:50:13', 1, 6, 7.70, 1.40, 1.60, NULL, 81, 317, 3033, 0.70, 27.7, 771, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1651, '2025-05-30 11:38:13', 1, 6, 7.30, 2.30, 1.20, NULL, 92, 283, 3538, 0.80, 29.0, 664, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1652, '2025-05-29 11:00:13', 1, 6, 7.30, 1.90, 2.60, NULL, 88, 372, 3914, 0.50, 26.9, 683, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1653, '2025-05-28 11:29:13', 1, 6, 7.70, 2.40, 3.00, NULL, 88, 325, 3307, 0.70, 26.5, 698, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1654, '2025-05-27 11:26:13', 1, 6, 7.00, 3.00, 3.50, NULL, 112, 320, 3714, 0.40, 27.3, 670, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1655, '2025-05-26 09:10:13', 1, 6, 7.00, 1.40, 0.50, NULL, 113, 337, 3366, 0.30, 29.6, 659, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1656, '2025-05-25 11:00:13', 1, 6, 7.70, 1.00, 3.10, NULL, 107, 309, 3255, 0.90, 26.6, 749, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1657, '2025-05-24 08:53:13', 1, 6, 7.60, 2.30, 1.30, NULL, 97, 385, 3484, 0.80, 28.8, 751, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1658, '2025-05-23 11:34:13', 1, 6, 7.20, 1.90, 0.70, NULL, 85, 303, 3545, 0.00, 29.3, 785, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1659, '2025-05-22 11:22:13', 1, 6, 7.70, 2.60, 0.70, NULL, 117, 275, 3392, 0.20, 26.2, 689, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1660, '2025-05-21 09:37:13', 1, 6, 7.10, 1.30, 2.10, NULL, 117, 314, 3224, 0.70, 26.4, 686, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1661, '2025-05-20 08:34:13', 1, 6, 7.00, 2.90, 1.00, NULL, 94, 364, 3027, 0.80, 29.5, 765, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1662, '2025-05-19 11:29:13', 1, 6, 7.60, 1.40, 1.70, NULL, 90, 346, 3734, 1.00, 26.7, 660, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1663, '2025-05-18 09:15:13', 1, 6, 7.50, 2.10, 1.00, NULL, 90, 283, 3104, 0.40, 27.4, 657, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1664, '2025-05-17 11:53:13', 1, 6, 7.70, 0.50, 3.50, NULL, 101, 216, 3031, 0.90, 26.6, 677, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1665, '2025-05-16 10:17:13', 1, 6, 7.40, 1.40, 0.70, NULL, 107, 391, 3713, 0.20, 28.1, 799, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1666, '2025-05-15 09:40:13', 1, 6, 7.50, 2.40, 3.30, NULL, 112, 329, 3448, 0.60, 27.1, 704, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1667, '2025-05-14 11:21:13', 1, 6, 7.10, 1.80, 1.00, NULL, 102, 339, 3805, 0.90, 27.2, 717, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1668, '2025-05-13 08:31:13', 1, 6, 7.50, 1.80, 2.40, NULL, 118, 208, 3456, 0.90, 29.0, 795, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1669, '2025-05-12 10:25:13', 1, 6, 7.60, 2.40, 3.10, NULL, 81, 252, 3650, 0.10, 27.5, 736, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1670, '2025-05-11 08:18:13', 1, 6, 7.60, 0.60, 0.80, NULL, 116, 399, 3181, 0.90, 26.9, 710, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1671, '2025-05-10 10:53:13', 1, 6, 7.20, 1.70, 2.00, NULL, 99, 238, 3248, 0.80, 28.4, 740, 'Routine monthly check', '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1672, '2025-05-09 10:33:13', 1, 6, 7.60, 2.30, 1.40, NULL, 105, 297, 3775, 0.70, 27.0, 755, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1673, '2025-05-08 10:18:13', 1, 6, 7.70, 2.60, 2.30, NULL, 87, 218, 3578, 0.40, 27.8, 782, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1674, '2025-05-07 08:26:13', 1, 6, 7.40, 2.00, 1.70, NULL, 110, 226, 3258, 0.10, 28.2, 720, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1675, '2025-05-06 08:27:13', 1, 6, 7.20, 2.20, 1.70, NULL, 90, 230, 3403, 1.00, 29.5, 663, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1676, '2025-05-05 08:23:13', 1, 6, 7.40, 2.60, 2.70, NULL, 112, 364, 3850, 0.20, 27.3, 773, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1677, '2025-05-04 11:47:13', 1, 6, 7.00, 2.90, 1.70, NULL, 108, 300, 3822, 0.40, 28.9, 671, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1678, '2025-05-03 11:20:13', 1, 6, 7.20, 2.80, 0.90, NULL, 104, 333, 3120, 0.20, 29.6, 670, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1679, '2025-05-02 10:51:13', 1, 6, 7.30, 2.70, 1.20, NULL, 111, 322, 3526, 0.60, 29.4, 675, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1680, '2025-05-01 10:36:13', 1, 6, 7.30, 0.80, 1.60, NULL, 87, 356, 3276, 0.30, 26.7, 755, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1681, '2025-04-30 09:49:13', 1, 6, 7.50, 3.00, 2.90, NULL, 97, 318, 3225, 0.10, 28.7, 715, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1682, '2025-04-29 11:54:13', 1, 6, 7.50, 3.00, 2.20, NULL, 108, 368, 3595, 0.70, 27.8, 681, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1683, '2025-04-28 10:00:13', 1, 6, 7.00, 2.30, 1.30, NULL, 111, 378, 3473, 0.00, 28.7, 770, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1684, '2025-04-27 11:58:13', 1, 6, 7.80, 1.20, 1.20, NULL, 117, 209, 3267, 0.90, 26.8, 679, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1685, '2025-04-26 08:44:13', 1, 6, 7.40, 1.80, 1.00, NULL, 92, 261, 3539, 0.30, 27.6, 747, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1686, '2025-04-25 09:28:13', 1, 6, 7.50, 0.70, 2.90, NULL, 114, 274, 3721, 0.20, 28.2, 652, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1687, '2025-04-24 08:24:13', 1, 6, 7.10, 1.30, 3.40, NULL, 84, 390, 3937, 0.00, 28.6, 791, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1688, '2025-04-23 08:00:13', 1, 6, 7.60, 1.90, 2.90, NULL, 118, 390, 3557, 1.00, 27.7, 719, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1689, '2025-04-22 08:24:13', 1, 6, 7.60, 2.60, 0.60, NULL, 89, 218, 3227, 0.90, 26.7, 663, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1690, '2025-04-21 08:40:13', 1, 6, 7.30, 1.90, 2.20, NULL, 101, 239, 3731, 0.30, 28.8, 762, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1691, '2025-04-20 08:50:13', 1, 6, 7.60, 0.90, 2.50, NULL, 109, 332, 3680, 0.40, 26.8, 710, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1692, '2025-04-19 10:43:13', 1, 6, 7.30, 2.90, 0.50, NULL, 107, 377, 3344, 0.80, 26.3, 771, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1693, '2025-04-18 10:52:13', 1, 6, 7.00, 2.60, 2.70, NULL, 107, 385, 3777, 0.10, 29.6, 790, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1694, '2025-04-17 09:45:13', 1, 6, 7.60, 1.20, 0.50, NULL, 104, 397, 3480, 0.60, 28.6, 793, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1695, '2025-04-16 11:06:13', 1, 6, 7.50, 2.60, 3.30, NULL, 98, 265, 3456, 0.30, 28.2, 742, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1696, '2025-04-15 08:32:13', 1, 6, 7.10, 0.70, 0.80, NULL, 85, 207, 3127, 0.50, 26.2, 682, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1697, '2025-04-14 10:29:13', 1, 6, 7.60, 2.70, 2.80, NULL, 118, 358, 3620, 0.40, 27.2, 660, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1698, '2025-04-13 09:47:13', 1, 6, 7.80, 1.10, 2.90, NULL, 95, 360, 3836, 0.80, 29.4, 670, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1699, '2025-04-12 11:36:13', 1, 6, 7.10, 0.70, 0.80, NULL, 113, 239, 3040, 0.50, 26.3, 683, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1700, '2025-04-11 08:31:13', 1, 6, 7.80, 1.30, 3.20, NULL, 91, 219, 3594, 0.40, 28.9, 710, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1701, '2025-04-10 09:11:13', 1, 6, 7.70, 1.50, 1.60, NULL, 111, 340, 3623, 0.00, 29.5, 765, 'Routine monthly check', '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1702, '2025-04-09 11:09:13', 1, 6, 7.10, 2.10, 1.80, NULL, 97, 285, 3127, 0.80, 26.5, 672, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1703, '2025-04-08 11:50:13', 1, 6, 7.00, 0.90, 1.40, NULL, 91, 211, 3923, 0.30, 26.7, 686, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1704, '2025-04-07 09:02:13', 1, 6, 7.40, 2.30, 2.00, NULL, 83, 249, 3579, 0.30, 26.7, 714, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1705, '2025-04-06 09:03:13', 1, 6, 7.80, 2.80, 1.30, NULL, 116, 233, 3260, 0.40, 26.2, 682, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1706, '2025-04-05 11:18:13', 1, 6, 7.00, 0.80, 0.50, NULL, 98, 277, 3958, 0.80, 28.5, 784, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1707, '2025-04-04 11:11:13', 1, 6, 7.10, 1.10, 1.00, NULL, 80, 373, 3995, 0.20, 28.6, 694, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1708, '2025-04-03 08:15:13', 1, 6, 7.60, 1.80, 3.50, NULL, 115, 276, 3944, 0.80, 29.0, 658, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1709, '2025-04-02 10:10:13', 1, 6, 7.60, 2.30, 1.00, NULL, 89, 208, 3038, 0.50, 28.2, 709, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1710, '2025-04-01 08:47:13', 1, 6, 7.70, 0.70, 1.00, NULL, 114, 266, 3605, 0.50, 28.0, 664, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1711, '2025-03-31 08:08:13', 1, 6, 7.00, 2.50, 0.70, NULL, 91, 309, 3706, 0.60, 28.5, 713, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1712, '2025-03-30 09:06:13', 1, 6, 7.00, 2.70, 1.00, NULL, 106, 208, 3377, 0.00, 27.1, 732, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1713, '2025-03-29 10:11:13', 1, 6, 7.40, 3.00, 3.50, NULL, 94, 216, 3569, 0.60, 29.5, 652, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1714, '2025-03-28 09:33:13', 1, 6, 7.00, 1.30, 2.30, NULL, 104, 296, 3316, 0.20, 30.0, 655, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1715, '2025-03-27 08:59:13', 1, 6, 7.80, 1.80, 3.00, NULL, 113, 340, 3263, 0.40, 29.2, 708, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1716, '2025-03-26 09:47:13', 1, 6, 7.00, 1.60, 0.70, NULL, 114, 295, 3494, 0.70, 27.9, 794, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1717, '2025-03-25 09:05:13', 1, 6, 7.70, 2.40, 1.50, NULL, 101, 261, 3140, 0.00, 26.7, 769, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1718, '2025-03-24 08:01:13', 1, 6, 7.00, 2.60, 1.90, NULL, 116, 252, 3416, 0.00, 29.3, 713, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1719, '2025-03-23 11:01:13', 1, 6, 7.30, 1.50, 2.90, NULL, 111, 233, 3206, 0.80, 28.9, 673, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1720, '2025-03-22 10:21:13', 1, 6, 7.70, 1.00, 1.90, NULL, 94, 276, 3002, 0.10, 29.0, 671, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1721, '2025-03-21 11:54:13', 1, 6, 7.10, 1.30, 1.00, NULL, 84, 399, 3889, 0.80, 28.0, 718, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1722, '2025-03-20 10:17:13', 1, 6, 7.10, 0.90, 3.50, NULL, 94, 396, 3767, 0.30, 28.8, 672, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1723, '2025-03-19 08:59:13', 1, 6, 7.50, 1.50, 1.50, NULL, 105, 296, 3838, 0.30, 28.9, 658, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1724, '2025-03-18 11:30:13', 1, 6, 7.50, 2.50, 3.40, NULL, 80, 365, 3812, 0.30, 27.1, 766, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1725, '2025-03-17 11:01:13', 1, 6, 7.30, 1.90, 3.10, NULL, 90, 205, 3545, 0.50, 28.1, 714, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1726, '2025-03-16 08:22:13', 1, 6, 7.20, 2.60, 1.20, NULL, 99, 335, 3075, 1.00, 29.4, 696, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1727, '2025-03-15 10:50:13', 1, 6, 7.50, 2.90, 2.30, NULL, 95, 298, 3773, 0.90, 27.1, 654, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1728, '2025-03-14 11:49:13', 1, 6, 7.40, 1.60, 1.60, NULL, 99, 202, 3413, 0.40, 26.9, 731, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1729, '2025-03-13 10:47:13', 1, 6, 7.50, 0.60, 3.40, NULL, 94, 250, 3517, 0.00, 28.3, 714, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1730, '2025-03-12 09:55:13', 1, 6, 7.30, 2.10, 1.60, NULL, 104, 395, 3031, 0.30, 29.3, 650, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1731, '2025-03-11 09:16:13', 1, 6, 7.40, 0.90, 0.90, NULL, 89, 285, 3430, 0.80, 29.4, 798, 'Routine monthly check', '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1732, '2025-03-10 10:30:13', 1, 6, 7.60, 2.60, 1.80, NULL, 100, 345, 3111, 0.70, 27.2, 679, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1733, '2025-03-09 09:08:13', 1, 6, 7.20, 1.30, 3.40, NULL, 109, 270, 3827, 0.30, 28.5, 671, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1734, '2025-03-08 08:04:13', 1, 6, 7.50, 2.10, 2.50, NULL, 109, 264, 3656, 0.80, 27.2, 674, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1735, '2025-03-07 11:00:13', 1, 6, 7.70, 0.50, 2.70, NULL, 97, 221, 3330, 0.70, 27.1, 704, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1736, '2025-03-06 08:41:13', 1, 6, 7.30, 1.60, 3.10, NULL, 80, 202, 3270, 0.00, 28.0, 783, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1737, '2025-03-05 11:05:13', 1, 6, 7.40, 1.60, 1.00, NULL, 95, 326, 3934, 0.10, 27.9, 704, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1738, '2025-03-04 10:55:13', 1, 6, 7.30, 2.00, 1.80, NULL, 114, 298, 3210, 0.80, 27.8, 675, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1739, '2025-03-03 09:11:13', 1, 6, 7.30, 0.60, 2.80, NULL, 96, 332, 3037, 0.00, 27.5, 772, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1740, '2025-03-02 10:24:13', 1, 6, 7.10, 2.00, 2.40, NULL, 83, 350, 3626, 0.20, 30.0, 708, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1741, '2025-03-01 11:59:13', 1, 6, 7.80, 0.80, 2.70, NULL, 83, 268, 3733, 0.60, 26.6, 793, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1742, '2025-02-28 08:10:13', 1, 6, 7.10, 2.00, 0.90, NULL, 88, 388, 3970, 0.10, 29.1, 712, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1743, '2025-02-27 10:41:13', 1, 6, 7.20, 1.70, 2.50, NULL, 108, 268, 3123, 0.70, 26.1, 703, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1744, '2025-02-26 08:06:13', 1, 6, 7.40, 1.40, 0.50, NULL, 112, 278, 3085, 0.00, 26.6, 716, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1745, '2025-02-25 08:35:13', 1, 6, 7.00, 0.50, 0.60, NULL, 114, 378, 3699, 0.40, 28.2, 793, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1746, '2025-02-24 09:36:13', 1, 6, 7.00, 0.80, 3.00, NULL, 120, 379, 3140, 0.80, 29.0, 760, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1747, '2025-02-23 08:48:13', 1, 6, 7.30, 2.20, 1.80, NULL, 97, 323, 3750, 0.70, 28.3, 728, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1748, '2025-02-22 11:42:13', 1, 6, 7.40, 0.50, 1.20, NULL, 115, 377, 3764, 0.50, 28.0, 771, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1749, '2025-02-21 08:34:13', 1, 6, 7.70, 1.80, 2.00, NULL, 88, 369, 3955, 0.70, 27.9, 718, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1750, '2025-02-20 11:37:13', 1, 6, 7.40, 1.30, 1.30, NULL, 120, 248, 3727, 0.30, 29.6, 675, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1751, '2025-02-19 11:21:13', 1, 6, 7.20, 3.00, 0.70, NULL, 107, 384, 3246, 1.00, 29.0, 723, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1752, '2025-02-18 11:22:13', 1, 6, 7.50, 2.70, 1.20, NULL, 80, 365, 3425, 0.20, 29.5, 716, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1753, '2025-02-17 09:38:13', 1, 6, 7.30, 2.50, 0.90, NULL, 86, 369, 3435, 0.30, 28.1, 685, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1754, '2025-02-16 08:17:13', 1, 6, 7.40, 1.90, 3.10, NULL, 108, 315, 3514, 0.40, 28.5, 773, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1755, '2025-02-15 10:01:13', 1, 6, 7.70, 2.90, 2.80, NULL, 106, 386, 3581, 0.40, 28.5, 770, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1756, '2025-02-14 10:41:13', 1, 6, 7.60, 2.40, 0.90, NULL, 112, 363, 3911, 0.70, 29.1, 783, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1757, '2025-02-13 10:31:13', 1, 6, 7.00, 2.60, 0.60, NULL, 117, 326, 3970, 0.40, 27.6, 701, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1758, '2025-02-12 10:53:13', 1, 6, 7.10, 2.20, 1.10, NULL, 83, 214, 3830, 0.20, 26.4, 791, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1759, '2025-02-11 11:38:13', 1, 6, 7.80, 1.10, 3.40, NULL, 96, 292, 3892, 0.00, 26.3, 773, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1760, '2025-02-10 10:52:13', 1, 6, 7.30, 0.50, 2.00, NULL, 88, 362, 3451, 0.40, 27.8, 662, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1761, '2025-02-09 10:06:13', 1, 6, 7.10, 2.00, 1.10, NULL, 90, 277, 3285, 0.50, 27.1, 788, 'Routine monthly check', '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1762, '2025-02-08 08:10:13', 1, 6, 7.80, 2.50, 1.10, NULL, 116, 357, 3330, 0.70, 26.5, 769, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1763, '2025-02-07 11:57:13', 1, 6, 7.20, 1.30, 3.00, NULL, 110, 350, 3050, 0.70, 26.3, 673, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1764, '2025-02-06 08:16:13', 1, 6, 7.80, 2.80, 2.10, NULL, 106, 287, 3295, 0.90, 29.9, 740, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1765, '2025-02-05 08:09:13', 1, 6, 7.50, 2.50, 1.70, NULL, 94, 310, 3646, 0.10, 27.3, 683, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1766, '2025-02-04 09:21:13', 1, 6, 7.60, 2.30, 2.70, NULL, 85, 263, 3358, 0.00, 26.7, 799, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1767, '2025-02-03 09:30:13', 1, 6, 7.70, 2.60, 3.20, NULL, 118, 389, 3891, 0.70, 26.3, 675, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1768, '2025-02-02 08:06:13', 1, 6, 7.10, 0.50, 2.70, NULL, 98, 222, 3545, 0.70, 28.3, 775, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1769, '2025-02-01 11:16:13', 1, 6, 7.80, 1.80, 3.50, NULL, 90, 230, 3649, 0.00, 28.8, 757, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1770, '2025-01-31 11:09:13', 1, 6, 7.70, 0.80, 2.60, NULL, 115, 206, 3660, 0.60, 27.0, 656, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1771, '2025-01-30 10:40:13', 1, 6, 7.70, 0.50, 2.20, NULL, 83, 241, 3785, 0.20, 29.5, 658, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1772, '2025-01-29 09:03:13', 1, 6, 7.70, 0.90, 1.20, NULL, 90, 254, 3918, 1.00, 26.3, 729, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1773, '2025-01-28 09:41:13', 1, 6, 7.30, 1.40, 3.40, NULL, 95, 275, 3042, 0.70, 27.9, 685, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1774, '2025-01-27 11:57:13', 1, 6, 7.30, 1.50, 2.80, NULL, 80, 287, 3660, 0.70, 28.3, 680, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1775, '2025-01-26 11:47:13', 1, 6, 7.30, 2.30, 2.20, NULL, 110, 325, 3992, 0.50, 26.2, 752, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1776, '2025-01-25 09:17:13', 1, 6, 7.30, 0.50, 3.00, NULL, 81, 262, 3685, 0.80, 29.5, 749, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1777, '2025-01-24 10:07:13', 1, 6, 7.10, 1.80, 0.80, NULL, 113, 271, 3944, 0.50, 29.8, 708, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1778, '2025-01-23 08:46:13', 1, 6, 7.10, 1.40, 1.20, NULL, 85, 340, 3916, 0.20, 27.2, 670, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1779, '2025-01-22 10:05:13', 1, 6, 7.30, 2.50, 2.10, NULL, 80, 290, 3454, 0.90, 27.4, 736, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1780, '2025-01-21 08:17:13', 1, 6, 7.30, 3.00, 2.50, NULL, 115, 318, 3681, 0.80, 28.6, 669, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1781, '2025-01-20 11:36:13', 1, 6, 7.80, 1.20, 2.00, NULL, 117, 310, 3209, 0.60, 28.0, 779, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1782, '2025-01-19 10:37:13', 1, 6, 7.40, 1.60, 0.60, NULL, 93, 383, 3749, 1.00, 30.0, 714, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1783, '2025-01-18 09:46:13', 1, 6, 7.70, 1.70, 3.00, NULL, 92, 293, 3824, 0.10, 26.6, 754, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1784, '2025-01-17 10:12:13', 1, 6, 7.50, 1.80, 1.90, NULL, 97, 329, 3486, 0.20, 27.6, 770, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1785, '2025-01-16 08:15:13', 1, 6, 7.50, 1.70, 2.40, NULL, 117, 354, 3760, 0.70, 27.3, 703, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1786, '2025-01-15 09:54:13', 1, 6, 7.40, 2.10, 2.00, NULL, 92, 270, 3849, 1.00, 26.2, 759, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1787, '2025-01-14 11:07:13', 1, 6, 7.40, 3.00, 2.60, NULL, 86, 360, 3151, 0.00, 29.6, 689, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1788, '2025-01-13 10:50:13', 1, 6, 7.30, 2.60, 1.30, NULL, 113, 284, 3213, 0.20, 29.9, 742, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1789, '2025-01-12 10:25:13', 1, 6, 7.10, 1.10, 2.40, NULL, 106, 342, 3687, 0.60, 26.6, 724, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1790, '2025-01-11 10:23:13', 1, 6, 7.20, 1.00, 2.70, NULL, 119, 321, 3675, 0.30, 27.4, 749, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1791, '2025-01-10 11:08:13', 1, 6, 7.70, 0.60, 1.20, NULL, 117, 286, 3758, 0.50, 27.3, 775, 'Routine monthly check', '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1792, '2025-01-09 11:24:13', 1, 6, 7.80, 1.10, 3.40, NULL, 102, 301, 3458, 1.00, 28.5, 694, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1793, '2025-01-08 10:28:13', 1, 6, 7.50, 0.70, 1.80, NULL, 84, 252, 3848, 0.80, 27.1, 731, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1794, '2025-01-07 09:54:13', 1, 6, 7.30, 0.60, 1.60, NULL, 80, 262, 3360, 0.10, 26.5, 716, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1795, '2025-01-06 10:45:13', 1, 6, 7.30, 0.90, 1.00, NULL, 115, 276, 3713, 0.20, 29.7, 797, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1796, '2025-01-05 11:26:13', 1, 6, 7.50, 0.90, 1.80, NULL, 103, 228, 3109, 0.00, 26.6, 713, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1797, '2025-01-04 11:32:13', 1, 6, 7.10, 0.80, 2.50, NULL, 98, 381, 3934, 0.20, 26.2, 716, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1798, '2025-01-03 09:35:13', 1, 6, 7.70, 2.20, 2.10, NULL, 89, 259, 3321, 1.00, 28.2, 675, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1799, '2025-01-02 10:25:13', 1, 6, 7.60, 0.80, 0.70, NULL, 93, 247, 3826, 0.60, 28.7, 792, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1800, '2025-01-01 11:36:13', 1, 6, 7.30, 2.40, 1.40, NULL, 91, 325, 3537, 0.80, 26.1, 760, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1801, '2024-12-31 11:06:13', 1, 6, 7.80, 0.50, 3.00, NULL, 114, 299, 3006, 0.90, 29.6, 711, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1802, '2024-12-30 08:36:13', 1, 6, 7.30, 0.50, 0.90, NULL, 88, 379, 3155, 0.50, 29.2, 768, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1803, '2024-12-29 11:21:13', 1, 6, 7.20, 2.20, 3.20, NULL, 87, 320, 3479, 0.60, 27.4, 679, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1804, '2024-12-28 11:02:13', 1, 6, 7.10, 2.80, 1.20, NULL, 113, 359, 3708, 0.80, 27.8, 763, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1805, '2024-12-27 11:42:13', 1, 6, 7.70, 2.60, 2.10, NULL, 86, 220, 3138, 0.00, 27.2, 791, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1806, '2024-12-26 11:04:13', 1, 6, 7.70, 2.10, 0.60, NULL, 97, 382, 3151, 0.50, 29.8, 673, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1807, '2024-12-25 10:32:13', 1, 6, 7.60, 2.50, 1.70, NULL, 87, 232, 3690, 0.80, 27.9, 729, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1808, '2024-12-24 08:35:13', 1, 6, 7.50, 2.40, 0.50, NULL, 86, 308, 3261, 0.40, 27.7, 711, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1809, '2024-12-23 10:18:13', 1, 6, 7.00, 2.70, 2.30, NULL, 115, 239, 3240, 0.20, 26.4, 757, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1810, '2024-12-22 08:03:13', 1, 6, 7.30, 0.80, 2.00, NULL, 118, 276, 3634, 0.70, 27.0, 781, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1811, '2024-12-21 10:45:13', 1, 6, 7.30, 0.60, 2.10, NULL, 85, 344, 3805, 0.90, 29.4, 666, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1812, '2024-12-20 10:25:13', 1, 6, 7.10, 1.70, 2.60, NULL, 111, 263, 3295, 0.90, 28.9, 694, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1813, '2024-12-19 08:21:13', 1, 6, 7.20, 1.60, 3.30, NULL, 88, 270, 3918, 0.50, 29.7, 762, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1814, '2024-12-18 11:32:13', 1, 6, 7.80, 0.60, 2.30, NULL, 106, 209, 3692, 0.80, 29.3, 724, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1815, '2024-12-17 09:55:13', 1, 6, 7.60, 1.80, 3.50, NULL, 91, 349, 3489, 0.20, 26.0, 797, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1816, '2024-12-16 11:20:13', 1, 6, 7.80, 2.60, 3.50, NULL, 81, 388, 3796, 1.00, 26.7, 654, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1817, '2024-12-15 08:23:13', 1, 6, 7.40, 2.10, 1.30, NULL, 118, 371, 3506, 0.50, 28.4, 704, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1818, '2024-12-14 08:30:13', 1, 6, 7.20, 1.30, 2.10, NULL, 110, 318, 3014, 0.40, 29.8, 757, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1819, '2024-12-13 10:26:13', 1, 6, 7.40, 0.60, 0.80, NULL, 84, 203, 3170, 1.00, 26.1, 654, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1820, '2024-12-12 08:15:13', 1, 6, 7.00, 2.90, 2.50, NULL, 119, 374, 3845, 0.20, 29.0, 708, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1821, '2024-12-11 11:24:13', 1, 6, 7.10, 1.60, 0.80, NULL, 108, 337, 3869, 0.30, 29.2, 728, 'Routine monthly check', '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1822, '2024-12-10 09:05:13', 1, 6, 7.80, 3.00, 2.90, NULL, 109, 350, 3072, 1.00, 27.4, 782, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1823, '2024-12-09 11:13:13', 1, 6, 7.30, 1.60, 0.80, NULL, 110, 282, 3374, 0.90, 26.4, 698, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1824, '2024-12-08 11:56:13', 1, 6, 7.30, 1.60, 2.70, NULL, 87, 291, 3338, 0.10, 29.8, 707, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1825, '2024-12-07 08:54:13', 1, 6, 7.40, 2.70, 1.40, NULL, 91, 386, 3675, 0.80, 27.1, 716, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1826, '2025-12-06 10:12:13', 1, 7, 7.60, 1.80, 2.50, NULL, 90, 315, 3372, 0.40, 26.4, 795, 'Routine monthly check', '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1827, '2025-12-05 08:23:13', 1, 7, 7.00, 0.60, 3.40, NULL, 110, 233, 3271, 0.40, 27.8, 745, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1828, '2025-12-04 10:26:13', 1, 7, 7.10, 0.70, 0.80, NULL, 80, 380, 3692, 0.40, 29.6, 717, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1829, '2025-12-03 08:17:13', 1, 7, 7.00, 2.70, 3.20, NULL, 92, 212, 3999, 0.70, 28.8, 720, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1830, '2025-12-02 10:47:13', 1, 7, 7.00, 2.90, 0.60, NULL, 107, 249, 3583, 0.70, 28.3, 660, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1831, '2025-12-01 10:51:13', 1, 7, 7.40, 2.80, 1.50, NULL, 94, 284, 3149, 0.60, 26.8, 782, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1832, '2025-11-30 08:39:13', 1, 7, 7.60, 2.20, 0.80, NULL, 109, 371, 3480, 0.00, 26.3, 656, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1833, '2025-11-29 09:53:13', 1, 7, 7.70, 2.80, 1.90, NULL, 108, 311, 3159, 0.30, 28.5, 722, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1834, '2025-11-28 10:05:13', 1, 7, 7.20, 2.70, 1.10, NULL, 99, 236, 3407, 0.80, 29.2, 773, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1835, '2025-11-27 11:54:13', 1, 7, 7.30, 1.30, 2.90, NULL, 103, 350, 3308, 1.00, 29.5, 760, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1836, '2025-11-26 10:35:13', 1, 7, 7.40, 1.70, 3.00, NULL, 105, 232, 3656, 0.40, 28.8, 788, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1837, '2025-11-25 10:44:13', 1, 7, 7.70, 3.00, 3.40, NULL, 112, 217, 3932, 0.30, 27.2, 748, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1838, '2025-11-24 10:52:13', 1, 7, 7.80, 0.50, 1.20, NULL, 99, 241, 3892, 0.90, 26.9, 656, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1839, '2025-11-23 11:10:13', 1, 7, 7.40, 1.90, 0.60, NULL, 114, 359, 3296, 0.10, 28.1, 731, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1840, '2025-11-22 10:56:13', 1, 7, 7.50, 2.70, 3.40, NULL, 93, 266, 3229, 0.60, 29.4, 747, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1841, '2025-11-21 10:06:13', 1, 7, 7.80, 1.20, 0.60, NULL, 91, 203, 3031, 0.70, 26.0, 795, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1842, '2025-11-20 11:52:13', 1, 7, 7.40, 0.50, 2.20, NULL, 94, 352, 3386, 0.30, 28.7, 717, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1843, '2025-11-19 09:27:13', 1, 7, 7.10, 2.00, 1.90, NULL, 88, 361, 3135, 0.30, 26.1, 778, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1844, '2025-11-18 09:39:13', 1, 7, 7.00, 2.00, 2.10, NULL, 113, 356, 3296, 1.00, 26.2, 678, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1845, '2025-11-17 09:11:13', 1, 7, 7.00, 1.80, 2.20, NULL, 97, 297, 3865, 1.00, 27.7, 697, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1846, '2025-11-16 10:54:13', 1, 7, 7.10, 1.50, 2.80, NULL, 112, 348, 3103, 0.70, 28.1, 687, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1847, '2025-11-15 09:51:13', 1, 7, 7.70, 3.00, 1.60, NULL, 102, 225, 3185, 0.40, 28.6, 774, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1848, '2025-11-14 08:45:13', 1, 7, 7.50, 2.70, 3.10, NULL, 87, 393, 3922, 0.20, 29.7, 662, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1849, '2025-11-13 09:15:13', 1, 7, 7.40, 1.30, 0.60, NULL, 84, 280, 3009, 0.10, 29.9, 742, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1850, '2025-11-12 09:55:13', 1, 7, 7.20, 2.60, 1.30, NULL, 109, 205, 3966, 0.40, 28.6, 675, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1851, '2025-11-11 08:18:13', 1, 7, 7.50, 2.10, 0.50, NULL, 97, 207, 3337, 0.00, 26.7, 796, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1852, '2025-11-10 11:11:13', 1, 7, 7.40, 2.10, 0.90, NULL, 80, 311, 3513, 0.60, 29.4, 689, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1853, '2025-11-09 09:36:13', 1, 7, 7.60, 1.30, 1.80, NULL, 92, 336, 3906, 0.10, 29.1, 735, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1854, '2025-11-08 11:42:13', 1, 7, 7.50, 2.50, 1.90, NULL, 93, 270, 3902, 0.10, 27.0, 705, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1855, '2025-11-07 10:37:13', 1, 7, 7.60, 1.00, 2.20, NULL, 100, 212, 3363, 0.50, 28.7, 750, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1856, '2025-11-06 10:13:13', 1, 7, 7.00, 1.20, 0.70, NULL, 113, 341, 3193, 0.60, 29.8, 703, 'Routine monthly check', '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1857, '2025-11-05 11:24:13', 1, 7, 7.80, 2.00, 1.50, NULL, 109, 285, 3979, 0.60, 26.3, 706, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1858, '2025-11-04 09:32:13', 1, 7, 7.50, 0.90, 2.70, NULL, 86, 209, 3030, 0.00, 26.8, 702, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1859, '2025-11-03 11:29:13', 1, 7, 7.80, 2.90, 1.10, NULL, 86, 378, 3724, 0.60, 29.0, 766, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1860, '2025-11-02 08:59:13', 1, 7, 7.80, 2.10, 0.70, NULL, 117, 382, 3974, 0.00, 27.3, 778, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1861, '2025-11-01 11:54:13', 1, 7, 7.70, 1.30, 2.30, NULL, 91, 218, 3257, 0.70, 26.8, 658, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1862, '2025-10-31 09:53:13', 1, 7, 7.30, 1.20, 3.00, NULL, 87, 391, 3723, 0.80, 27.3, 697, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1863, '2025-10-30 10:29:13', 1, 7, 7.60, 2.30, 1.90, NULL, 111, 237, 3448, 0.40, 26.7, 800, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1864, '2025-10-29 08:13:13', 1, 7, 7.60, 2.00, 1.80, NULL, 93, 260, 3589, 0.00, 28.2, 691, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1865, '2025-10-28 10:16:13', 1, 7, 7.60, 1.60, 3.40, NULL, 84, 246, 3208, 0.60, 27.9, 738, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1866, '2025-10-27 10:32:13', 1, 7, 7.60, 2.80, 2.10, NULL, 83, 357, 3815, 0.90, 26.7, 684, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1867, '2025-10-26 10:08:13', 1, 7, 7.10, 0.70, 2.10, NULL, 100, 340, 3875, 0.10, 29.1, 754, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1868, '2025-10-25 09:59:13', 1, 7, 7.40, 3.00, 2.30, NULL, 89, 377, 3706, 0.70, 28.7, 721, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1869, '2025-10-24 10:27:13', 1, 7, 7.30, 2.00, 1.30, NULL, 91, 359, 3392, 0.60, 26.0, 711, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1870, '2025-10-23 09:04:13', 1, 7, 7.80, 0.90, 1.60, NULL, 90, 333, 3298, 0.10, 27.1, 663, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1871, '2025-10-22 10:58:13', 1, 7, 7.80, 2.00, 3.00, NULL, 119, 358, 3115, 0.20, 26.8, 657, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1872, '2025-10-21 10:32:13', 1, 7, 7.60, 2.60, 3.40, NULL, 89, 389, 3560, 0.20, 29.9, 758, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1873, '2025-10-20 08:46:13', 1, 7, 7.00, 1.90, 2.20, NULL, 104, 314, 3241, 0.90, 28.6, 698, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1874, '2025-10-19 09:26:13', 1, 7, 7.60, 2.80, 0.70, NULL, 107, 225, 3026, 0.50, 26.0, 765, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1875, '2025-10-18 10:13:13', 1, 7, 7.20, 1.70, 2.90, NULL, 102, 213, 3652, 0.80, 29.7, 789, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1876, '2025-10-17 09:08:13', 1, 7, 7.10, 2.60, 2.00, NULL, 80, 391, 3666, 1.00, 27.6, 702, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1877, '2025-10-16 09:25:13', 1, 7, 7.20, 1.90, 2.00, NULL, 80, 366, 3530, 0.00, 28.7, 745, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1878, '2025-10-15 10:57:13', 1, 7, 7.80, 2.80, 3.30, NULL, 81, 253, 3300, 0.70, 29.9, 676, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1879, '2025-10-14 11:10:13', 1, 7, 7.70, 2.60, 2.20, NULL, 89, 211, 3163, 0.10, 26.5, 672, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1880, '2025-10-13 11:33:13', 1, 7, 7.50, 2.40, 1.10, NULL, 85, 319, 3535, 0.90, 28.8, 710, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1881, '2025-10-12 11:53:13', 1, 7, 7.40, 2.00, 2.00, NULL, 111, 319, 3391, 0.40, 27.3, 732, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1882, '2025-10-11 08:38:13', 1, 7, 7.70, 2.60, 3.10, NULL, 103, 208, 3592, 0.90, 28.4, 651, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1883, '2025-10-10 11:45:13', 1, 7, 7.80, 2.00, 0.80, NULL, 104, 206, 3326, 0.10, 28.2, 695, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1884, '2025-10-09 09:33:13', 1, 7, 7.30, 1.80, 0.60, NULL, 103, 206, 3841, 0.90, 29.2, 750, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1885, '2025-10-08 11:50:13', 1, 7, 7.50, 2.40, 1.80, NULL, 80, 275, 3316, 0.50, 26.1, 705, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1886, '2025-10-07 09:07:13', 1, 7, 7.80, 0.50, 2.70, NULL, 101, 335, 3438, 0.00, 27.2, 771, 'Routine monthly check', '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1887, '2025-10-06 09:51:13', 1, 7, 7.80, 1.70, 1.40, NULL, 89, 336, 3708, 0.80, 26.5, 766, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1888, '2025-10-05 08:17:13', 1, 7, 7.00, 2.60, 3.40, NULL, 88, 335, 3847, 0.70, 27.5, 670, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1889, '2025-10-04 08:24:13', 1, 7, 7.80, 1.90, 1.40, NULL, 94, 375, 3276, 0.50, 27.7, 760, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1890, '2025-10-03 08:01:13', 1, 7, 7.30, 1.00, 1.10, NULL, 101, 236, 3582, 0.80, 27.3, 783, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1891, '2025-10-02 08:30:13', 1, 7, 7.50, 2.80, 1.50, NULL, 100, 289, 3041, 0.20, 29.8, 667, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1892, '2025-10-01 08:07:13', 1, 7, 7.20, 1.60, 1.30, NULL, 104, 204, 3576, 0.10, 29.5, 791, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1893, '2025-09-30 11:47:13', 1, 7, 7.70, 2.60, 3.40, NULL, 89, 317, 3486, 0.90, 29.7, 771, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1894, '2025-09-29 11:31:13', 1, 7, 7.10, 0.90, 3.40, NULL, 102, 221, 3936, 0.20, 27.3, 750, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1895, '2025-09-28 09:43:13', 1, 7, 7.30, 3.00, 0.50, NULL, 86, 316, 3115, 0.80, 26.6, 657, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1896, '2025-09-27 10:16:13', 1, 7, 7.00, 1.70, 3.20, NULL, 111, 256, 3854, 0.00, 29.5, 774, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1897, '2025-09-26 09:18:13', 1, 7, 7.20, 1.10, 0.60, NULL, 87, 366, 3442, 0.30, 27.6, 751, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1898, '2025-09-25 10:47:13', 1, 7, 7.80, 0.70, 3.00, NULL, 106, 232, 3935, 0.50, 27.0, 749, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1899, '2025-09-24 11:20:13', 1, 7, 7.80, 0.80, 3.10, NULL, 112, 251, 3323, 0.90, 29.8, 679, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1900, '2025-09-23 10:17:13', 1, 7, 7.60, 2.80, 2.70, NULL, 85, 396, 3396, 0.30, 28.6, 724, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1901, '2025-09-22 08:16:13', 1, 7, 7.00, 1.70, 2.00, NULL, 96, 352, 3190, 0.20, 27.4, 674, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1902, '2025-09-21 09:35:13', 1, 7, 7.40, 1.20, 3.10, NULL, 114, 282, 3666, 0.60, 27.0, 702, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1903, '2025-09-20 11:02:13', 1, 7, 7.50, 1.10, 1.80, NULL, 91, 351, 3832, 0.30, 28.2, 744, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1904, '2025-09-19 11:43:13', 1, 7, 7.80, 2.30, 2.00, NULL, 88, 396, 3674, 0.70, 27.4, 698, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1905, '2025-09-18 10:12:13', 1, 7, 7.70, 0.50, 3.20, NULL, 99, 223, 3311, 0.90, 27.5, 742, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1906, '2025-09-17 11:56:13', 1, 7, 7.20, 2.40, 2.70, NULL, 86, 345, 3466, 0.40, 28.0, 712, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1907, '2025-09-16 08:04:13', 1, 7, 7.00, 1.10, 1.30, NULL, 103, 361, 3418, 0.50, 26.3, 661, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1908, '2025-09-15 08:57:13', 1, 7, 7.20, 1.10, 0.50, NULL, 110, 361, 3915, 0.90, 28.3, 753, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1909, '2025-09-14 09:58:13', 1, 7, 7.70, 1.50, 2.40, NULL, 80, 311, 3199, 0.20, 28.2, 653, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1910, '2025-09-13 10:09:13', 1, 7, 7.30, 1.60, 2.80, NULL, 89, 342, 3361, 1.00, 29.7, 789, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1911, '2025-09-12 11:07:13', 1, 7, 7.60, 1.10, 2.90, NULL, 88, 342, 3746, 0.00, 28.5, 774, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1912, '2025-09-11 08:37:13', 1, 7, 7.60, 1.00, 1.00, NULL, 86, 222, 3691, 0.70, 27.0, 705, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1913, '2025-09-10 10:25:13', 1, 7, 7.40, 2.20, 2.00, NULL, 104, 400, 3427, 1.00, 26.2, 732, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1914, '2025-09-09 10:23:13', 1, 7, 7.00, 1.70, 3.20, NULL, 98, 366, 3126, 0.90, 30.0, 692, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1915, '2025-09-08 10:00:13', 1, 7, 7.70, 2.30, 2.50, NULL, 97, 389, 3122, 0.70, 29.9, 751, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1916, '2025-09-07 08:22:13', 1, 7, 7.30, 1.10, 1.80, NULL, 97, 304, 3655, 0.70, 27.9, 720, 'Routine monthly check', '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1917, '2025-09-06 09:12:13', 1, 7, 7.30, 2.40, 0.90, NULL, 87, 289, 3629, 0.80, 27.8, 742, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1918, '2025-09-05 11:38:13', 1, 7, 7.00, 0.70, 1.80, NULL, 112, 217, 3779, 0.10, 26.4, 751, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1919, '2025-09-04 11:25:13', 1, 7, 7.60, 1.10, 0.50, NULL, 84, 236, 3483, 0.70, 27.7, 747, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1920, '2025-09-03 11:26:13', 1, 7, 7.70, 1.70, 3.10, NULL, 99, 260, 3975, 0.20, 27.2, 713, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1921, '2025-09-02 08:28:13', 1, 7, 7.50, 2.50, 2.40, NULL, 108, 220, 3974, 0.90, 26.2, 733, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1922, '2025-09-01 08:10:13', 1, 7, 7.40, 1.10, 1.60, NULL, 104, 372, 3635, 0.30, 26.2, 728, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1923, '2025-08-31 10:06:13', 1, 7, 7.40, 2.00, 2.70, NULL, 111, 224, 3478, 0.60, 26.9, 727, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1924, '2025-08-30 08:18:13', 1, 7, 7.10, 0.60, 1.90, NULL, 98, 226, 3603, 0.50, 30.0, 719, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1925, '2025-08-29 09:22:13', 1, 7, 7.20, 1.90, 2.10, NULL, 107, 337, 3406, 0.40, 26.7, 792, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1926, '2025-08-28 09:02:13', 1, 7, 7.30, 1.30, 3.40, NULL, 95, 332, 3115, 0.30, 29.7, 695, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1927, '2025-08-27 11:28:13', 1, 7, 7.70, 1.70, 1.00, NULL, 89, 256, 3955, 0.50, 28.6, 711, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1928, '2025-08-26 11:44:13', 1, 7, 7.00, 1.20, 3.10, NULL, 87, 325, 3853, 0.50, 27.6, 726, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1929, '2025-08-25 10:57:13', 1, 7, 7.70, 0.70, 2.80, NULL, 111, 383, 3800, 0.70, 28.2, 678, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1930, '2025-08-24 09:21:13', 1, 7, 7.00, 2.30, 2.50, NULL, 117, 254, 3616, 0.70, 27.9, 771, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1931, '2025-08-23 08:31:13', 1, 7, 7.20, 1.40, 3.10, NULL, 108, 341, 3905, 0.10, 30.0, 666, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1932, '2025-08-22 10:05:13', 1, 7, 7.80, 2.10, 3.10, NULL, 92, 322, 3821, 0.80, 28.4, 738, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1933, '2025-08-21 11:53:13', 1, 7, 7.00, 2.90, 1.80, NULL, 92, 362, 3137, 0.70, 29.4, 772, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1934, '2025-08-20 10:40:13', 1, 7, 7.50, 1.70, 2.50, NULL, 87, 365, 3709, 0.30, 27.5, 712, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1935, '2025-08-19 09:55:13', 1, 7, 7.80, 1.90, 2.80, NULL, 94, 380, 3079, 0.70, 26.2, 666, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1936, '2025-08-18 10:30:13', 1, 7, 7.10, 0.80, 2.40, NULL, 103, 218, 3099, 1.00, 26.1, 686, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1937, '2025-08-17 10:32:13', 1, 7, 7.00, 2.70, 0.60, NULL, 115, 364, 3745, 0.30, 27.6, 652, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1938, '2025-08-16 11:00:13', 1, 7, 7.50, 2.40, 1.20, NULL, 104, 290, 3179, 0.00, 27.0, 717, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1939, '2025-08-15 09:37:13', 1, 7, 7.40, 0.50, 1.70, NULL, 105, 259, 3427, 1.00, 26.0, 693, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1940, '2025-08-14 09:54:13', 1, 7, 7.40, 2.00, 3.20, NULL, 106, 304, 3723, 1.00, 26.6, 741, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1941, '2025-08-13 11:34:13', 1, 7, 7.10, 2.90, 2.90, NULL, 93, 359, 3710, 0.30, 29.3, 680, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1942, '2025-08-12 10:41:13', 1, 7, 7.80, 1.40, 1.10, NULL, 103, 291, 3850, 1.00, 27.1, 788, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1943, '2025-08-11 10:33:13', 1, 7, 7.30, 0.50, 2.40, NULL, 103, 248, 3260, 0.20, 28.6, 684, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1944, '2025-08-10 09:47:13', 1, 7, 7.10, 2.10, 1.00, NULL, 117, 386, 3019, 0.40, 27.4, 685, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1945, '2025-08-09 09:06:13', 1, 7, 7.40, 2.70, 0.50, NULL, 111, 224, 3033, 0.30, 29.1, 650, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1946, '2025-08-08 08:44:13', 1, 7, 7.70, 0.60, 1.40, NULL, 106, 203, 3540, 0.40, 26.5, 763, 'Routine monthly check', '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1947, '2025-08-07 09:07:13', 1, 7, 7.60, 1.80, 3.20, NULL, 100, 259, 3207, 0.60, 27.4, 694, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1948, '2025-08-06 11:26:13', 1, 7, 7.80, 0.70, 2.40, NULL, 86, 279, 3420, 0.00, 28.1, 666, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1949, '2025-08-05 08:11:13', 1, 7, 7.80, 2.20, 2.70, NULL, 113, 200, 3926, 0.80, 26.6, 781, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1950, '2025-08-04 10:36:13', 1, 7, 7.00, 1.50, 0.70, NULL, 84, 336, 3599, 0.30, 29.1, 670, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1951, '2025-08-03 11:29:13', 1, 7, 7.50, 1.40, 1.30, NULL, 120, 281, 3562, 0.80, 26.1, 773, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1952, '2025-08-02 09:32:13', 1, 7, 7.30, 2.00, 2.70, NULL, 116, 289, 3932, 1.00, 29.1, 780, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1953, '2025-08-01 09:18:13', 1, 7, 7.60, 0.50, 1.90, NULL, 114, 210, 3949, 1.00, 28.0, 783, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1954, '2025-07-31 10:32:13', 1, 7, 7.10, 0.80, 2.80, NULL, 92, 282, 3171, 1.00, 28.5, 702, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1955, '2025-07-30 11:54:13', 1, 7, 7.70, 1.60, 2.70, NULL, 85, 363, 3022, 0.60, 27.1, 707, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1956, '2025-07-29 08:24:13', 1, 7, 7.50, 1.20, 1.50, NULL, 81, 207, 3067, 0.90, 28.1, 777, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1957, '2025-07-28 11:35:13', 1, 7, 7.10, 2.90, 1.40, NULL, 88, 243, 3615, 0.70, 27.9, 788, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1958, '2025-07-27 10:54:13', 1, 7, 7.50, 0.90, 1.50, NULL, 96, 377, 3927, 0.60, 27.7, 795, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1959, '2025-07-26 08:24:13', 1, 7, 7.00, 2.90, 3.10, NULL, 85, 314, 3122, 0.70, 27.6, 758, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1960, '2025-07-25 08:42:13', 1, 7, 7.60, 2.90, 0.90, NULL, 118, 297, 3842, 0.30, 26.9, 770, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1961, '2025-07-24 10:52:13', 1, 7, 7.30, 1.30, 1.40, NULL, 81, 392, 3182, 0.60, 29.2, 746, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1962, '2025-07-23 11:54:13', 1, 7, 7.70, 2.90, 2.00, NULL, 105, 252, 3959, 0.60, 29.4, 794, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1963, '2025-07-22 11:54:13', 1, 7, 7.20, 1.90, 2.40, NULL, 103, 214, 3574, 0.80, 29.1, 701, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1964, '2025-07-21 09:53:13', 1, 7, 7.40, 2.70, 2.30, NULL, 106, 378, 3731, 1.00, 30.0, 718, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1965, '2025-07-20 09:37:13', 1, 7, 7.80, 0.50, 1.20, NULL, 90, 375, 3159, 0.60, 26.9, 756, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1966, '2025-07-19 10:17:13', 1, 7, 7.80, 0.80, 2.20, NULL, 105, 287, 3927, 0.60, 28.0, 693, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1967, '2025-07-18 10:45:13', 1, 7, 7.70, 1.40, 2.30, NULL, 107, 352, 3677, 0.80, 26.9, 798, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1968, '2025-07-17 11:36:13', 1, 7, 7.50, 2.50, 1.80, NULL, 105, 399, 3378, 0.10, 28.7, 663, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1969, '2025-07-16 10:41:13', 1, 7, 7.60, 0.90, 3.40, NULL, 88, 382, 3320, 0.90, 28.9, 724, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1970, '2025-07-15 10:43:13', 1, 7, 7.70, 2.20, 2.40, NULL, 82, 352, 3277, 0.80, 29.6, 711, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1971, '2025-07-14 10:06:13', 1, 7, 7.80, 1.30, 1.80, NULL, 96, 305, 3087, 0.10, 28.2, 658, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1972, '2025-07-13 10:39:13', 1, 7, 7.40, 2.30, 3.00, NULL, 95, 278, 3690, 0.20, 29.8, 718, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1973, '2025-07-12 10:40:13', 1, 7, 7.00, 1.00, 2.00, NULL, 94, 395, 3549, 0.80, 26.9, 691, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1974, '2025-07-11 10:25:13', 1, 7, 7.40, 0.60, 3.10, NULL, 96, 280, 3638, 1.00, 28.4, 733, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1975, '2025-07-10 08:42:13', 1, 7, 7.60, 3.00, 2.80, NULL, 102, 202, 3678, 0.90, 29.0, 785, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1976, '2025-07-09 11:37:13', 1, 7, 7.40, 0.80, 2.00, NULL, 81, 340, 3020, 1.00, 26.8, 717, 'Routine monthly check', '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1977, '2025-07-08 08:53:13', 1, 7, 7.10, 0.90, 1.80, NULL, 100, 349, 3704, 0.60, 26.3, 711, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1978, '2025-07-07 11:13:13', 1, 7, 7.00, 2.40, 1.10, NULL, 117, 273, 3750, 0.30, 29.5, 764, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1979, '2025-07-06 08:21:13', 1, 7, 7.70, 1.90, 2.10, NULL, 81, 228, 3061, 1.00, 27.2, 710, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1980, '2025-07-05 10:36:13', 1, 7, 7.10, 1.50, 2.50, NULL, 94, 357, 3068, 0.20, 26.0, 719, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1981, '2025-07-04 11:03:13', 1, 7, 7.60, 0.90, 2.10, NULL, 102, 208, 3537, 0.10, 26.9, 656, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1982, '2025-07-03 09:20:13', 1, 7, 7.20, 1.20, 3.50, NULL, 105, 213, 3931, 0.60, 28.0, 792, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1983, '2025-07-02 08:25:13', 1, 7, 7.50, 1.80, 3.30, NULL, 82, 306, 3327, 0.20, 28.0, 760, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1984, '2025-07-01 11:17:13', 1, 7, 7.40, 1.20, 3.50, NULL, 83, 249, 3060, 0.20, 27.4, 683, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1985, '2025-06-30 09:33:13', 1, 7, 7.20, 2.30, 2.90, NULL, 83, 214, 3415, 0.60, 29.1, 651, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1986, '2025-06-29 09:32:13', 1, 7, 7.40, 1.50, 0.70, NULL, 120, 220, 3030, 0.10, 29.4, 742, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1987, '2025-06-28 09:36:13', 1, 7, 7.30, 1.90, 0.90, NULL, 101, 392, 3449, 1.00, 28.8, 680, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1988, '2025-06-27 10:44:13', 1, 7, 7.80, 1.00, 1.90, NULL, 96, 383, 3508, 0.30, 28.8, 764, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1989, '2025-06-26 10:22:13', 1, 7, 7.60, 0.90, 3.50, NULL, 107, 323, 3662, 1.00, 30.0, 799, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1990, '2025-06-25 10:11:13', 1, 7, 7.40, 0.80, 0.70, NULL, 85, 211, 3884, 0.80, 29.2, 716, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1991, '2025-06-24 11:11:13', 1, 7, 7.10, 2.40, 3.00, NULL, 117, 242, 3083, 0.90, 28.2, 670, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1992, '2025-06-23 09:32:13', 1, 7, 7.20, 2.30, 3.10, NULL, 84, 373, 3282, 0.60, 28.3, 669, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1993, '2025-06-22 11:41:13', 1, 7, 7.80, 3.00, 3.40, NULL, 103, 333, 3282, 0.70, 29.2, 714, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1994, '2025-06-21 10:41:13', 1, 7, 7.20, 2.30, 1.60, NULL, 109, 300, 3555, 1.00, 27.4, 728, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1995, '2025-06-20 09:54:13', 1, 7, 7.80, 2.40, 1.50, NULL, 102, 211, 3830, 0.10, 29.1, 777, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1996, '2025-06-19 09:59:13', 1, 7, 7.70, 0.90, 1.60, NULL, 82, 211, 3448, 1.00, 29.0, 730, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1997, '2025-06-18 10:10:13', 1, 7, 7.60, 2.50, 1.30, NULL, 83, 370, 3206, 0.10, 28.6, 684, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1998, '2025-06-17 10:35:13', 1, 7, 7.50, 2.30, 1.10, NULL, 87, 277, 3558, 0.20, 26.2, 700, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (1999, '2025-06-16 10:07:13', 1, 7, 7.60, 0.80, 1.20, NULL, 113, 332, 3084, 0.20, 28.0, 757, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2000, '2025-06-15 10:15:13', 1, 7, 7.20, 2.40, 1.90, NULL, 109, 222, 3175, 0.70, 28.6, 733, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2001, '2025-06-14 10:45:13', 1, 7, 7.70, 2.80, 1.00, NULL, 97, 297, 3121, 0.20, 26.4, 707, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2002, '2025-06-13 08:35:13', 1, 7, 7.70, 2.70, 2.40, NULL, 85, 335, 3392, 0.30, 26.8, 712, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2003, '2025-06-12 10:49:13', 1, 7, 7.60, 1.00, 2.30, NULL, 94, 259, 3707, 0.40, 28.5, 751, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2004, '2025-06-11 10:03:13', 1, 7, 7.40, 1.50, 1.30, NULL, 98, 297, 3850, 0.80, 27.7, 800, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2005, '2025-06-10 11:46:13', 1, 7, 7.00, 1.70, 1.00, NULL, 110, 286, 3247, 0.30, 29.8, 725, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2006, '2025-06-09 09:08:13', 1, 7, 7.00, 2.10, 1.60, NULL, 117, 281, 3081, 0.40, 26.4, 657, 'Routine monthly check', '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2007, '2025-06-08 10:09:13', 1, 7, 7.30, 2.40, 2.30, NULL, 99, 384, 3976, 0.70, 30.0, 740, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2008, '2025-06-07 11:21:13', 1, 7, 7.50, 1.90, 0.50, NULL, 97, 370, 3567, 0.60, 28.7, 722, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2009, '2025-06-06 09:28:13', 1, 7, 7.60, 2.90, 3.30, NULL, 101, 379, 3920, 0.00, 27.5, 724, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2010, '2025-06-05 10:19:13', 1, 7, 7.50, 2.20, 3.10, NULL, 89, 245, 3417, 0.20, 27.2, 783, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2011, '2025-06-04 11:23:13', 1, 7, 7.00, 0.70, 0.60, NULL, 110, 341, 3836, 0.00, 28.8, 671, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2012, '2025-06-03 10:07:13', 1, 7, 7.10, 1.40, 3.40, NULL, 108, 260, 3691, 0.80, 29.1, 738, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2013, '2025-06-02 09:37:13', 1, 7, 7.10, 1.70, 2.80, NULL, 115, 278, 3800, 1.00, 26.8, 711, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2014, '2025-06-01 08:16:13', 1, 7, 7.70, 0.80, 2.30, NULL, 95, 395, 3467, 0.70, 29.9, 694, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2015, '2025-05-31 09:05:13', 1, 7, 7.50, 1.30, 1.00, NULL, 106, 243, 3787, 0.30, 27.5, 707, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2016, '2025-05-30 11:30:13', 1, 7, 7.50, 1.80, 2.80, NULL, 95, 290, 3045, 0.20, 29.9, 662, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2017, '2025-05-29 08:50:13', 1, 7, 7.00, 1.20, 2.50, NULL, 94, 302, 3998, 0.30, 28.8, 669, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2018, '2025-05-28 11:09:13', 1, 7, 7.00, 1.60, 1.70, NULL, 107, 263, 3383, 0.10, 27.0, 761, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2019, '2025-05-27 11:20:13', 1, 7, 7.60, 2.10, 1.90, NULL, 104, 242, 3858, 0.30, 30.0, 794, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2020, '2025-05-26 09:22:13', 1, 7, 7.10, 1.70, 2.60, NULL, 118, 200, 3671, 0.80, 29.8, 753, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2021, '2025-05-25 11:50:13', 1, 7, 7.50, 2.10, 3.50, NULL, 100, 291, 3563, 0.10, 26.5, 668, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2022, '2025-05-24 09:36:13', 1, 7, 7.10, 2.20, 2.70, NULL, 98, 267, 3240, 0.40, 26.5, 704, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2023, '2025-05-23 10:35:13', 1, 7, 7.00, 1.20, 1.40, NULL, 112, 220, 3541, 0.60, 26.1, 738, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2024, '2025-05-22 11:01:13', 1, 7, 7.60, 1.00, 2.10, NULL, 106, 394, 3624, 0.30, 26.6, 675, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2025, '2025-05-21 10:58:13', 1, 7, 7.30, 1.20, 2.40, NULL, 91, 388, 3844, 0.30, 28.0, 799, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2026, '2025-05-20 09:46:13', 1, 7, 7.00, 2.60, 2.20, NULL, 116, 251, 3147, 0.00, 28.6, 714, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2027, '2025-05-19 09:16:13', 1, 7, 7.40, 1.30, 0.80, NULL, 83, 337, 3053, 1.00, 29.5, 705, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2028, '2025-05-18 08:23:13', 1, 7, 7.70, 2.70, 2.30, NULL, 87, 219, 3116, 0.10, 26.6, 775, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2029, '2025-05-17 08:56:13', 1, 7, 7.00, 2.30, 0.80, NULL, 80, 270, 3099, 0.50, 28.2, 673, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2030, '2025-05-16 09:35:13', 1, 7, 7.50, 3.00, 3.10, NULL, 96, 356, 3308, 0.90, 28.7, 716, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2031, '2025-05-15 10:40:13', 1, 7, 7.80, 2.20, 1.50, NULL, 92, 221, 3117, 0.60, 29.1, 733, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2032, '2025-05-14 10:35:13', 1, 7, 7.50, 2.90, 1.10, NULL, 103, 304, 3743, 0.70, 27.3, 787, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2033, '2025-05-13 11:59:13', 1, 7, 7.50, 1.20, 1.90, NULL, 101, 384, 3967, 0.80, 27.5, 748, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2034, '2025-05-12 11:22:13', 1, 7, 7.10, 2.00, 1.30, NULL, 89, 396, 3269, 0.80, 28.8, 666, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2035, '2025-05-11 08:25:13', 1, 7, 7.30, 2.50, 1.30, NULL, 86, 384, 3379, 0.20, 27.3, 795, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2036, '2025-05-10 10:20:13', 1, 7, 7.30, 1.60, 3.10, NULL, 106, 370, 3366, 0.80, 27.9, 717, 'Routine monthly check', '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2037, '2025-05-09 10:51:13', 1, 7, 7.50, 2.80, 2.20, NULL, 113, 388, 3475, 0.80, 26.8, 747, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2038, '2025-05-08 09:57:13', 1, 7, 7.40, 3.00, 1.60, NULL, 80, 269, 3031, 0.70, 28.2, 736, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2039, '2025-05-07 09:26:13', 1, 7, 7.20, 2.00, 2.60, NULL, 111, 220, 3678, 0.30, 29.2, 666, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2040, '2025-05-06 08:23:13', 1, 7, 7.30, 1.90, 3.40, NULL, 88, 250, 3836, 0.80, 27.6, 672, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2041, '2025-05-05 08:16:13', 1, 7, 7.20, 1.90, 1.40, NULL, 116, 272, 3664, 0.20, 26.0, 777, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2042, '2025-05-04 08:51:13', 1, 7, 7.30, 2.70, 1.90, NULL, 85, 298, 3738, 1.00, 26.7, 709, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2043, '2025-05-03 10:43:13', 1, 7, 7.50, 2.40, 0.50, NULL, 81, 265, 3755, 1.00, 29.2, 697, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2044, '2025-05-02 08:40:13', 1, 7, 7.70, 1.50, 1.60, NULL, 117, 305, 3057, 0.30, 26.7, 713, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2045, '2025-05-01 10:06:13', 1, 7, 7.10, 2.40, 3.30, NULL, 80, 271, 3563, 0.60, 26.0, 682, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2046, '2025-04-30 09:06:13', 1, 7, 7.50, 0.80, 1.20, NULL, 117, 271, 3665, 0.80, 28.7, 694, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2047, '2025-04-29 10:26:13', 1, 7, 7.50, 2.30, 1.10, NULL, 96, 376, 3161, 0.60, 28.8, 767, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2048, '2025-04-28 11:32:13', 1, 7, 7.30, 2.60, 1.30, NULL, 115, 266, 3545, 1.00, 29.9, 794, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2049, '2025-04-27 09:40:13', 1, 7, 7.60, 0.90, 3.50, NULL, 107, 348, 3167, 0.90, 29.5, 751, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2050, '2025-04-26 10:56:13', 1, 7, 7.10, 2.10, 1.80, NULL, 87, 367, 3993, 0.40, 27.1, 756, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2051, '2025-04-25 08:49:13', 1, 7, 7.20, 2.40, 3.00, NULL, 87, 213, 3146, 0.60, 29.9, 707, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2052, '2025-04-24 10:53:13', 1, 7, 7.80, 0.60, 1.90, NULL, 117, 275, 3828, 0.40, 27.6, 738, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2053, '2025-04-23 10:16:13', 1, 7, 7.30, 1.40, 2.40, NULL, 103, 282, 3929, 0.30, 27.2, 737, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2054, '2025-04-22 09:42:13', 1, 7, 7.80, 1.00, 2.00, NULL, 95, 246, 3199, 0.40, 28.6, 779, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2055, '2025-04-21 09:09:13', 1, 7, 7.40, 2.10, 2.40, NULL, 113, 287, 3699, 1.00, 27.5, 798, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2056, '2025-04-20 08:01:13', 1, 7, 7.60, 2.40, 1.90, NULL, 110, 229, 3690, 0.10, 28.0, 764, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2057, '2025-04-19 10:21:13', 1, 7, 7.80, 0.90, 2.60, NULL, 95, 378, 3621, 0.70, 29.1, 721, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2058, '2025-04-18 10:35:13', 1, 7, 7.40, 0.70, 1.60, NULL, 96, 288, 3345, 0.60, 28.3, 699, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2059, '2025-04-17 08:12:13', 1, 7, 7.10, 2.30, 3.50, NULL, 83, 271, 3077, 0.80, 28.9, 664, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2060, '2025-04-16 11:33:13', 1, 7, 7.60, 2.20, 1.10, NULL, 106, 246, 3643, 0.20, 26.2, 688, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2061, '2025-04-15 10:02:13', 1, 7, 7.10, 0.80, 1.70, NULL, 114, 397, 3582, 0.70, 29.3, 709, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2062, '2025-04-14 11:19:13', 1, 7, 7.30, 2.10, 2.40, NULL, 83, 364, 3966, 0.20, 27.0, 789, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2063, '2025-04-13 10:25:13', 1, 7, 7.50, 1.60, 2.00, NULL, 112, 241, 3170, 0.10, 26.9, 773, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2064, '2025-04-12 10:08:13', 1, 7, 7.80, 2.00, 1.70, NULL, 82, 320, 3946, 0.80, 27.9, 799, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2065, '2025-04-11 11:14:13', 1, 7, 7.00, 2.50, 2.60, NULL, 104, 301, 3262, 0.20, 29.5, 691, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2066, '2025-04-10 10:03:13', 1, 7, 7.00, 1.80, 1.70, NULL, 80, 289, 3213, 0.70, 27.4, 664, 'Routine monthly check', '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2067, '2025-04-09 10:43:13', 1, 7, 7.70, 2.10, 2.00, NULL, 97, 396, 3107, 0.80, 30.0, 727, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2068, '2025-04-08 09:06:13', 1, 7, 7.70, 1.90, 2.10, NULL, 101, 327, 3435, 0.80, 29.8, 723, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2069, '2025-04-07 08:11:13', 1, 7, 7.50, 2.10, 2.60, NULL, 88, 337, 3165, 0.00, 26.9, 765, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2070, '2025-04-06 09:50:13', 1, 7, 7.10, 2.90, 1.70, NULL, 118, 381, 3747, 1.00, 28.0, 659, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2071, '2025-04-05 10:27:13', 1, 7, 7.80, 3.00, 2.40, NULL, 80, 231, 3808, 0.40, 30.0, 736, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2072, '2025-04-04 11:08:13', 1, 7, 7.70, 1.10, 3.50, NULL, 101, 332, 3545, 0.60, 26.5, 650, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2073, '2025-04-03 09:01:13', 1, 7, 7.20, 0.50, 3.10, NULL, 82, 222, 3911, 0.60, 30.0, 662, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2074, '2025-04-02 09:16:13', 1, 7, 7.60, 0.80, 2.20, NULL, 93, 233, 3608, 0.20, 29.0, 773, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2075, '2025-04-01 11:04:13', 1, 7, 7.80, 0.60, 2.30, NULL, 89, 251, 3563, 0.30, 28.0, 698, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2076, '2025-03-31 11:31:13', 1, 7, 7.10, 0.90, 1.10, NULL, 111, 317, 3345, 0.70, 28.9, 671, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2077, '2025-03-30 11:56:13', 1, 7, 7.50, 2.30, 2.30, NULL, 103, 257, 3806, 0.70, 28.4, 773, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2078, '2025-03-29 09:46:13', 1, 7, 7.30, 1.00, 1.10, NULL, 85, 297, 3266, 0.20, 27.8, 725, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2079, '2025-03-28 08:21:13', 1, 7, 7.10, 2.70, 2.10, NULL, 117, 395, 3862, 0.10, 26.2, 686, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2080, '2025-03-27 10:48:13', 1, 7, 7.50, 1.80, 2.70, NULL, 87, 292, 3729, 0.00, 28.6, 794, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2081, '2025-03-26 10:34:13', 1, 7, 7.10, 2.50, 2.40, NULL, 110, 294, 3777, 0.30, 28.1, 669, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2082, '2025-03-25 08:18:13', 1, 7, 7.30, 0.60, 0.70, NULL, 83, 241, 3866, 0.20, 29.2, 795, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2083, '2025-03-24 10:41:13', 1, 7, 7.30, 2.00, 0.80, NULL, 81, 283, 3294, 0.40, 28.0, 705, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2084, '2025-03-23 10:44:13', 1, 7, 7.80, 2.30, 2.70, NULL, 91, 296, 3225, 0.50, 26.6, 710, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2085, '2025-03-22 09:15:13', 1, 7, 7.80, 0.90, 1.50, NULL, 109, 361, 3325, 1.00, 26.2, 787, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2086, '2025-03-21 09:26:13', 1, 7, 7.10, 1.80, 1.60, NULL, 91, 202, 3677, 0.90, 26.8, 651, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2087, '2025-03-20 09:24:13', 1, 7, 7.10, 2.90, 2.80, NULL, 118, 299, 3216, 0.20, 29.8, 768, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2088, '2025-03-19 09:59:13', 1, 7, 7.70, 1.60, 1.70, NULL, 109, 323, 3649, 0.40, 29.4, 719, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2089, '2025-03-18 10:56:13', 1, 7, 7.70, 2.40, 1.40, NULL, 97, 221, 3772, 0.20, 26.9, 675, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2090, '2025-03-17 11:42:13', 1, 7, 7.10, 2.40, 1.60, NULL, 101, 311, 3362, 0.80, 28.1, 773, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2091, '2025-03-16 10:07:13', 1, 7, 7.40, 1.50, 0.60, NULL, 102, 209, 3088, 0.10, 29.5, 689, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2092, '2025-03-15 10:07:13', 1, 7, 7.40, 1.40, 3.50, NULL, 120, 340, 3119, 0.80, 26.6, 660, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2093, '2025-03-14 08:03:13', 1, 7, 7.50, 2.60, 2.10, NULL, 120, 281, 3871, 0.60, 26.9, 761, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2094, '2025-03-13 08:49:13', 1, 7, 7.00, 0.90, 1.60, NULL, 95, 378, 3191, 0.80, 29.8, 678, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2095, '2025-03-12 08:30:13', 1, 7, 7.10, 1.00, 2.80, NULL, 100, 217, 3966, 0.10, 29.7, 676, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2096, '2025-03-11 08:52:13', 1, 7, 7.80, 1.80, 1.50, NULL, 102, 393, 3239, 0.30, 28.3, 688, 'Routine monthly check', '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2097, '2025-03-10 10:26:13', 1, 7, 7.50, 3.00, 1.10, NULL, 88, 397, 3197, 0.30, 27.7, 756, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2098, '2025-03-09 11:14:13', 1, 7, 7.30, 1.20, 1.20, NULL, 81, 283, 3061, 0.20, 29.4, 748, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2099, '2025-03-08 09:44:13', 1, 7, 7.30, 1.10, 1.10, NULL, 93, 332, 3922, 0.70, 27.4, 702, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2100, '2025-03-07 09:38:13', 1, 7, 7.10, 2.00, 1.40, NULL, 94, 275, 3996, 1.00, 27.9, 678, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2101, '2025-03-06 11:54:13', 1, 7, 7.30, 2.60, 2.00, NULL, 111, 200, 3553, 0.80, 28.2, 737, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2102, '2025-03-05 11:33:13', 1, 7, 7.40, 1.50, 2.80, NULL, 113, 348, 3640, 0.40, 30.0, 720, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2103, '2025-03-04 08:59:13', 1, 7, 7.80, 1.40, 1.60, NULL, 113, 307, 3875, 0.00, 26.7, 784, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2104, '2025-03-03 10:56:13', 1, 7, 7.40, 2.50, 2.00, NULL, 80, 232, 3431, 0.50, 29.7, 757, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2105, '2025-03-02 11:07:13', 1, 7, 7.50, 3.00, 0.60, NULL, 109, 296, 3310, 1.00, 27.9, 665, NULL, '2025-12-06 12:13:13', '2025-12-06 12:13:13');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2106, '2025-03-01 10:07:14', 1, 7, 7.40, 3.00, 1.40, NULL, 105, 395, 3825, 0.20, 29.7, 703, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2107, '2025-02-28 09:56:14', 1, 7, 7.20, 2.90, 3.20, NULL, 100, 374, 3037, 0.60, 27.5, 763, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2108, '2025-02-27 10:18:14', 1, 7, 7.00, 1.10, 2.90, NULL, 100, 225, 3399, 1.00, 29.0, 770, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2109, '2025-02-26 08:27:14', 1, 7, 7.70, 1.80, 1.50, NULL, 101, 382, 3914, 1.00, 28.7, 786, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2110, '2025-02-25 11:07:14', 1, 7, 7.20, 2.60, 0.70, NULL, 83, 333, 3920, 0.40, 29.2, 733, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2111, '2025-02-24 08:18:14', 1, 7, 7.40, 0.60, 1.50, NULL, 109, 261, 3813, 0.40, 28.9, 756, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2112, '2025-02-23 11:37:14', 1, 7, 7.80, 2.00, 3.00, NULL, 104, 358, 3976, 0.20, 28.7, 780, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2113, '2025-02-22 09:14:14', 1, 7, 7.20, 1.70, 0.90, NULL, 112, 217, 3111, 1.00, 27.0, 764, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2114, '2025-02-21 09:01:14', 1, 7, 7.10, 0.80, 1.10, NULL, 98, 213, 3655, 0.60, 29.2, 707, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2115, '2025-02-20 08:45:14', 1, 7, 7.80, 3.00, 1.40, NULL, 115, 226, 3954, 0.80, 28.7, 788, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2116, '2025-02-19 09:07:14', 1, 7, 7.40, 2.60, 1.70, NULL, 100, 371, 3630, 0.40, 28.9, 678, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2117, '2025-02-18 10:12:14', 1, 7, 7.60, 2.90, 1.80, NULL, 105, 339, 3199, 0.20, 29.2, 709, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2118, '2025-02-17 11:35:14', 1, 7, 7.50, 2.70, 2.00, NULL, 81, 375, 3461, 0.70, 28.5, 741, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2119, '2025-02-16 10:52:14', 1, 7, 7.70, 1.60, 1.10, NULL, 114, 230, 3700, 0.40, 28.9, 732, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2120, '2025-02-15 11:48:14', 1, 7, 7.80, 1.60, 1.90, NULL, 87, 214, 3526, 1.00, 27.5, 697, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2121, '2025-02-14 10:08:14', 1, 7, 7.20, 0.90, 2.90, NULL, 102, 374, 3726, 0.60, 29.5, 785, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2122, '2025-02-13 10:11:14', 1, 7, 7.50, 0.90, 2.60, NULL, 82, 206, 3598, 0.30, 29.6, 747, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2123, '2025-02-12 09:33:14', 1, 7, 7.50, 2.60, 3.10, NULL, 106, 341, 3791, 0.50, 29.8, 730, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2124, '2025-02-11 09:10:14', 1, 7, 7.40, 2.60, 2.70, NULL, 120, 269, 3039, 0.20, 27.3, 697, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2125, '2025-02-10 11:57:14', 1, 7, 7.50, 0.50, 2.10, NULL, 87, 394, 3675, 0.50, 28.1, 661, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2126, '2025-02-09 10:43:14', 1, 7, 7.70, 1.00, 1.90, NULL, 85, 390, 3571, 0.60, 28.1, 655, 'Routine monthly check', '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2127, '2025-02-08 09:42:14', 1, 7, 7.30, 2.60, 3.00, NULL, 91, 311, 3791, 0.70, 30.0, 757, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2128, '2025-02-07 09:16:14', 1, 7, 7.00, 2.20, 2.20, NULL, 107, 353, 3317, 0.00, 27.4, 690, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2129, '2025-02-06 09:14:14', 1, 7, 7.30, 0.90, 2.80, NULL, 97, 400, 3328, 0.70, 26.4, 674, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2130, '2025-02-05 10:54:14', 1, 7, 7.50, 0.50, 3.00, NULL, 109, 321, 3761, 0.00, 28.2, 758, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2131, '2025-02-04 08:05:14', 1, 7, 7.10, 2.20, 0.70, NULL, 98, 286, 3059, 0.60, 26.6, 744, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2132, '2025-02-03 08:46:14', 1, 7, 7.10, 2.60, 2.10, NULL, 107, 265, 3401, 0.10, 26.1, 706, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2133, '2025-02-02 10:39:14', 1, 7, 7.20, 2.20, 2.70, NULL, 117, 209, 3804, 0.60, 28.2, 792, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2134, '2025-02-01 10:04:14', 1, 7, 7.30, 1.50, 0.80, NULL, 93, 303, 3018, 0.20, 29.2, 664, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2135, '2025-01-31 08:27:14', 1, 7, 7.70, 2.00, 2.90, NULL, 86, 264, 3745, 0.00, 28.7, 697, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2136, '2025-01-30 08:24:14', 1, 7, 7.60, 2.70, 1.20, NULL, 84, 269, 3776, 0.60, 28.9, 722, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2137, '2025-01-29 11:24:14', 1, 7, 7.40, 2.40, 2.40, NULL, 89, 270, 3511, 0.60, 27.2, 724, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2138, '2025-01-28 10:04:14', 1, 7, 7.50, 1.60, 0.80, NULL, 96, 203, 3477, 0.80, 28.5, 768, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2139, '2025-01-27 10:26:14', 1, 7, 7.10, 2.50, 2.80, NULL, 87, 245, 3010, 1.00, 27.3, 767, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2140, '2025-01-26 08:26:14', 1, 7, 7.10, 2.40, 2.70, NULL, 120, 219, 3607, 0.40, 26.8, 791, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2141, '2025-01-25 11:16:14', 1, 7, 7.10, 1.10, 2.60, NULL, 103, 302, 3626, 0.80, 26.9, 659, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2142, '2025-01-24 08:41:14', 1, 7, 7.10, 1.80, 2.30, NULL, 102, 300, 3911, 0.10, 28.7, 732, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2143, '2025-01-23 11:39:14', 1, 7, 7.50, 3.00, 1.10, NULL, 100, 353, 3523, 0.30, 27.8, 702, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2144, '2025-01-22 08:07:14', 1, 7, 7.60, 2.90, 2.50, NULL, 112, 297, 3038, 0.70, 28.1, 659, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2145, '2025-01-21 09:04:14', 1, 7, 7.20, 1.50, 1.70, NULL, 84, 281, 3680, 0.80, 27.2, 745, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2146, '2025-01-20 11:25:14', 1, 7, 7.00, 0.50, 2.10, NULL, 100, 297, 3758, 0.30, 29.1, 674, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2147, '2025-01-19 09:18:14', 1, 7, 7.50, 2.20, 2.90, NULL, 96, 271, 3926, 0.50, 27.6, 706, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2148, '2025-01-18 10:54:14', 1, 7, 7.70, 2.30, 1.20, NULL, 114, 289, 3139, 0.40, 28.8, 759, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2149, '2025-01-17 08:31:14', 1, 7, 7.30, 2.80, 1.50, NULL, 96, 244, 3094, 0.50, 27.9, 758, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2150, '2025-01-16 11:28:14', 1, 7, 7.50, 3.00, 1.80, NULL, 105, 300, 3585, 0.10, 27.7, 700, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2151, '2025-01-15 10:29:14', 1, 7, 7.50, 3.00, 3.20, NULL, 99, 281, 3889, 0.60, 28.0, 765, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2152, '2025-01-14 08:28:14', 1, 7, 7.60, 2.00, 1.50, NULL, 91, 248, 3837, 0.80, 29.4, 760, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2153, '2025-01-13 10:05:14', 1, 7, 7.80, 1.40, 3.20, NULL, 119, 388, 3025, 0.70, 27.6, 652, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2154, '2025-01-12 08:01:14', 1, 7, 7.70, 0.90, 3.20, NULL, 110, 276, 3473, 0.00, 26.8, 761, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2155, '2025-01-11 11:25:14', 1, 7, 7.50, 0.80, 0.60, NULL, 109, 366, 3144, 0.90, 26.7, 793, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2156, '2025-01-10 11:01:14', 1, 7, 7.30, 1.90, 3.30, NULL, 115, 212, 3889, 0.10, 29.2, 659, 'Routine monthly check', '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2157, '2025-01-09 10:20:14', 1, 7, 7.40, 1.80, 2.20, NULL, 88, 377, 3611, 0.10, 26.9, 667, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2158, '2025-01-08 11:28:14', 1, 7, 7.70, 0.50, 2.00, NULL, 97, 343, 3696, 0.00, 28.0, 659, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2159, '2025-01-07 10:25:14', 1, 7, 7.30, 2.90, 3.00, NULL, 88, 213, 3913, 0.40, 28.6, 782, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2160, '2025-01-06 08:45:14', 1, 7, 7.40, 0.80, 2.70, NULL, 115, 229, 3793, 1.00, 27.3, 729, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2161, '2025-01-05 10:54:14', 1, 7, 7.20, 1.50, 0.60, NULL, 98, 350, 3932, 0.80, 28.0, 679, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2162, '2025-01-04 08:33:14', 1, 7, 7.10, 0.60, 2.50, NULL, 94, 260, 3103, 0.20, 29.2, 764, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2163, '2025-01-03 08:19:14', 1, 7, 7.00, 3.00, 1.10, NULL, 85, 206, 3855, 0.60, 27.5, 793, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2164, '2025-01-02 09:44:14', 1, 7, 7.30, 2.10, 1.90, NULL, 86, 302, 3008, 0.10, 30.0, 734, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2165, '2025-01-01 09:09:14', 1, 7, 7.60, 2.30, 3.40, NULL, 106, 233, 3575, 0.10, 26.7, 687, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2166, '2024-12-31 10:19:14', 1, 7, 7.30, 2.40, 3.10, NULL, 110, 248, 3304, 0.80, 29.4, 688, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2167, '2024-12-30 08:13:14', 1, 7, 7.00, 0.90, 0.90, NULL, 120, 308, 3092, 0.70, 29.4, 707, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2168, '2024-12-29 09:18:14', 1, 7, 7.20, 2.40, 0.60, NULL, 115, 351, 3887, 0.80, 26.3, 658, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2169, '2024-12-28 10:40:14', 1, 7, 7.60, 3.00, 1.80, NULL, 90, 296, 3070, 1.00, 27.1, 720, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2170, '2024-12-27 11:37:14', 1, 7, 7.30, 2.20, 1.20, NULL, 84, 387, 3231, 0.80, 29.7, 669, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2171, '2024-12-26 09:46:14', 1, 7, 7.30, 3.00, 2.00, NULL, 105, 294, 3863, 0.70, 27.6, 735, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2172, '2024-12-25 10:42:14', 1, 7, 7.50, 1.30, 2.40, NULL, 81, 341, 3876, 0.80, 26.0, 671, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2173, '2024-12-24 09:37:14', 1, 7, 7.00, 2.80, 0.50, NULL, 113, 349, 3749, 0.00, 28.0, 685, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2174, '2024-12-23 08:32:14', 1, 7, 7.40, 0.50, 0.50, NULL, 120, 231, 3767, 0.40, 29.6, 738, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2175, '2024-12-22 10:39:14', 1, 7, 7.40, 1.60, 3.20, NULL, 97, 207, 3824, 0.20, 26.8, 662, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2176, '2024-12-21 08:38:14', 1, 7, 7.00, 2.00, 1.10, NULL, 98, 271, 3826, 0.40, 28.9, 702, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2177, '2024-12-20 10:15:14', 1, 7, 7.30, 1.50, 0.90, NULL, 93, 282, 3239, 0.90, 28.4, 786, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2178, '2024-12-19 09:01:14', 1, 7, 7.00, 2.70, 1.00, NULL, 105, 394, 3509, 0.00, 29.9, 710, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2179, '2024-12-18 09:11:14', 1, 7, 7.60, 2.00, 2.50, NULL, 102, 292, 3848, 0.30, 27.4, 694, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2180, '2024-12-17 10:29:14', 1, 7, 7.30, 2.50, 0.90, NULL, 94, 225, 3927, 0.00, 27.5, 698, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2181, '2024-12-16 08:28:14', 1, 7, 7.20, 1.50, 0.70, NULL, 83, 272, 3136, 0.00, 27.7, 667, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2182, '2024-12-15 10:23:14', 1, 7, 7.70, 1.50, 0.50, NULL, 107, 327, 3182, 0.40, 28.5, 710, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2183, '2024-12-14 10:23:14', 1, 7, 7.60, 1.20, 2.00, NULL, 106, 343, 3364, 0.30, 28.9, 793, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2184, '2024-12-13 08:19:14', 1, 7, 7.40, 1.70, 3.30, NULL, 85, 389, 3350, 0.30, 29.7, 760, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2185, '2024-12-12 08:03:14', 1, 7, 7.70, 0.70, 0.90, NULL, 88, 308, 3829, 0.00, 30.0, 730, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2186, '2024-12-11 09:53:14', 1, 7, 7.20, 1.70, 2.30, NULL, 110, 290, 3198, 0.50, 28.8, 779, 'Routine monthly check', '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2187, '2024-12-10 09:45:14', 1, 7, 7.50, 1.40, 2.90, NULL, 93, 382, 3292, 0.00, 27.0, 727, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2188, '2024-12-09 09:13:14', 1, 7, 7.40, 0.90, 0.70, NULL, 100, 211, 3389, 0.50, 29.8, 761, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2189, '2024-12-08 08:05:14', 1, 7, 7.20, 1.40, 0.50, NULL, 114, 307, 3426, 0.20, 29.4, 701, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2190, '2024-12-07 11:06:14', 1, 7, 7.00, 2.30, 3.20, NULL, 102, 312, 3543, 0.30, 26.8, 660, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2191, '2025-12-06 08:53:14', 1, 8, 7.00, 2.80, 0.90, NULL, 86, 372, 3454, 1.00, 29.4, 756, 'Routine monthly check', '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2192, '2025-12-05 10:20:14', 1, 8, 7.40, 0.90, 1.00, NULL, 111, 301, 3820, 0.00, 27.7, 724, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2193, '2025-12-04 08:52:14', 1, 8, 7.20, 2.40, 1.30, NULL, 108, 387, 3251, 0.00, 27.2, 771, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2194, '2025-12-03 11:54:14', 1, 8, 7.20, 2.50, 2.30, NULL, 113, 281, 3735, 0.40, 27.1, 776, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2195, '2025-12-02 11:45:14', 1, 8, 7.50, 1.50, 3.40, NULL, 115, 331, 3809, 0.90, 29.9, 755, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2196, '2025-12-01 08:11:14', 1, 8, 7.00, 3.00, 3.40, NULL, 109, 317, 3912, 1.00, 26.5, 744, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2197, '2025-11-30 11:51:14', 1, 8, 7.40, 2.00, 1.80, NULL, 116, 208, 3431, 0.60, 26.2, 704, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2198, '2025-11-29 10:54:14', 1, 8, 7.30, 2.40, 1.50, NULL, 107, 215, 3123, 0.30, 26.5, 781, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2199, '2025-11-28 10:25:14', 1, 8, 7.50, 2.10, 3.10, NULL, 95, 383, 3149, 0.40, 28.1, 667, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2200, '2025-11-27 09:27:14', 1, 8, 7.50, 1.30, 2.00, NULL, 114, 243, 3151, 0.80, 26.6, 710, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2201, '2025-11-26 11:20:14', 1, 8, 7.30, 1.90, 1.70, NULL, 112, 285, 3558, 0.30, 27.4, 756, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2202, '2025-11-25 10:06:14', 1, 8, 7.80, 1.90, 2.40, NULL, 112, 356, 3978, 1.00, 27.7, 651, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2203, '2025-11-24 09:43:14', 1, 8, 7.50, 1.60, 2.80, NULL, 93, 344, 3767, 0.10, 27.8, 788, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2204, '2025-11-23 10:59:14', 1, 8, 7.80, 1.90, 3.30, NULL, 86, 392, 3571, 0.60, 26.5, 703, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2205, '2025-11-22 09:10:14', 1, 8, 7.80, 2.00, 1.60, NULL, 89, 369, 3610, 0.70, 28.3, 680, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2206, '2025-11-21 08:20:14', 1, 8, 7.50, 2.00, 0.50, NULL, 83, 228, 3431, 0.20, 27.9, 792, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2207, '2025-11-20 08:21:14', 1, 8, 7.40, 1.60, 2.20, NULL, 94, 204, 3594, 0.70, 26.1, 670, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2208, '2025-11-19 08:23:14', 1, 8, 7.40, 0.80, 0.50, NULL, 92, 335, 3309, 0.00, 26.3, 698, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2209, '2025-11-18 09:35:14', 1, 8, 7.40, 1.70, 3.10, NULL, 90, 273, 3787, 0.10, 28.8, 796, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2210, '2025-11-17 11:34:14', 1, 8, 7.80, 1.10, 1.40, NULL, 115, 384, 3884, 0.90, 29.3, 664, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2211, '2025-11-16 09:18:14', 1, 8, 7.10, 1.30, 3.40, NULL, 113, 323, 3401, 0.10, 29.4, 685, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2212, '2025-11-15 11:40:14', 1, 8, 7.10, 0.80, 3.20, NULL, 113, 314, 3689, 0.90, 27.9, 663, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2213, '2025-11-14 08:06:14', 1, 8, 7.00, 2.30, 2.20, NULL, 90, 274, 3890, 0.50, 29.7, 727, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2214, '2025-11-13 10:37:14', 1, 8, 7.60, 2.40, 2.70, NULL, 113, 333, 3318, 0.40, 29.4, 737, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2215, '2025-11-12 09:41:14', 1, 8, 7.50, 2.20, 2.50, NULL, 117, 305, 3249, 0.00, 26.6, 795, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2216, '2025-11-11 11:19:14', 1, 8, 7.10, 2.40, 0.70, NULL, 113, 315, 3388, 0.90, 28.4, 710, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2217, '2025-11-10 08:49:14', 1, 8, 7.10, 1.10, 0.70, NULL, 106, 232, 3141, 0.30, 27.9, 762, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2218, '2025-11-09 08:50:14', 1, 8, 7.00, 2.10, 0.70, NULL, 88, 359, 3608, 0.00, 27.7, 659, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2219, '2025-11-08 11:46:14', 1, 8, 7.40, 2.90, 3.00, NULL, 85, 396, 3093, 0.40, 26.1, 783, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2220, '2025-11-07 11:54:14', 1, 8, 7.20, 1.00, 1.10, NULL, 109, 273, 3910, 1.00, 28.2, 687, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2221, '2025-11-06 09:07:14', 1, 8, 7.20, 2.70, 0.60, NULL, 97, 257, 3444, 0.70, 26.7, 794, 'Routine monthly check', '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2222, '2025-11-05 08:48:14', 1, 8, 7.30, 2.50, 3.40, NULL, 99, 317, 3300, 0.40, 29.1, 704, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2223, '2025-11-04 08:21:14', 1, 8, 7.10, 2.50, 1.50, NULL, 101, 325, 3072, 1.00, 26.3, 769, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2224, '2025-11-03 08:10:14', 1, 8, 7.50, 1.90, 3.20, NULL, 92, 202, 3882, 0.10, 29.3, 729, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2225, '2025-11-02 09:19:14', 1, 8, 7.10, 1.20, 2.60, NULL, 85, 246, 3817, 0.40, 27.1, 704, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2226, '2025-11-01 08:29:14', 1, 8, 7.40, 0.60, 3.00, NULL, 93, 363, 3803, 0.50, 26.4, 795, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2227, '2025-10-31 11:40:14', 1, 8, 7.60, 0.50, 0.70, NULL, 84, 254, 3567, 0.10, 27.4, 701, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2228, '2025-10-30 09:36:14', 1, 8, 7.40, 3.00, 2.40, NULL, 117, 322, 3008, 0.40, 26.2, 693, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2229, '2025-10-29 11:28:14', 1, 8, 7.60, 1.30, 3.40, NULL, 101, 244, 3691, 0.90, 27.4, 720, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2230, '2025-10-28 11:38:14', 1, 8, 7.50, 2.40, 0.90, NULL, 116, 339, 3095, 0.70, 27.0, 737, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2231, '2025-10-27 11:27:14', 1, 8, 7.70, 1.40, 2.90, NULL, 114, 268, 3266, 0.20, 26.2, 741, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2232, '2025-10-26 08:59:14', 1, 8, 7.80, 2.50, 0.50, NULL, 110, 315, 3900, 0.50, 27.1, 718, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2233, '2025-10-25 08:28:14', 1, 8, 7.30, 2.40, 1.80, NULL, 81, 244, 3595, 0.90, 29.8, 758, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2234, '2025-10-24 10:20:14', 1, 8, 7.00, 2.60, 0.70, NULL, 114, 268, 3019, 0.90, 27.3, 663, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2235, '2025-10-23 10:22:14', 1, 8, 7.00, 2.40, 3.40, NULL, 119, 252, 3131, 0.50, 26.3, 701, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2236, '2025-10-22 10:56:14', 1, 8, 7.70, 0.60, 2.10, NULL, 87, 336, 3550, 0.20, 28.5, 685, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2237, '2025-10-21 08:16:14', 1, 8, 7.10, 1.30, 1.10, NULL, 93, 346, 3031, 0.20, 26.4, 741, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2238, '2025-10-20 08:20:14', 1, 8, 7.20, 1.70, 3.20, NULL, 90, 332, 3385, 1.00, 26.7, 777, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2239, '2025-10-19 08:15:14', 1, 8, 7.80, 1.70, 0.90, NULL, 85, 253, 3685, 0.30, 26.9, 754, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2240, '2025-10-18 10:15:14', 1, 8, 7.20, 2.60, 1.20, NULL, 90, 221, 3510, 0.80, 29.7, 741, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2241, '2025-10-17 11:48:14', 1, 8, 7.30, 0.50, 3.10, NULL, 103, 317, 3016, 0.40, 27.5, 711, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2242, '2025-10-16 09:38:14', 1, 8, 7.20, 1.70, 0.80, NULL, 92, 399, 3388, 0.40, 26.2, 731, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2243, '2025-10-15 09:17:14', 1, 8, 7.20, 1.40, 1.20, NULL, 113, 250, 3818, 0.40, 26.8, 797, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2244, '2025-10-14 09:45:14', 1, 8, 7.00, 0.90, 3.20, NULL, 95, 325, 3154, 0.70, 29.9, 687, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2245, '2025-10-13 09:05:14', 1, 8, 7.00, 0.60, 1.60, NULL, 115, 249, 3583, 0.60, 29.5, 763, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2246, '2025-10-12 11:32:14', 1, 8, 7.00, 1.40, 3.10, NULL, 109, 351, 3421, 0.60, 27.1, 738, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2247, '2025-10-11 11:38:14', 1, 8, 7.40, 2.60, 0.50, NULL, 110, 293, 3386, 0.50, 26.2, 710, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2248, '2025-10-10 08:30:14', 1, 8, 7.80, 1.60, 1.10, NULL, 106, 273, 3814, 0.00, 27.3, 663, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2249, '2025-10-09 09:23:14', 1, 8, 7.10, 2.70, 0.70, NULL, 81, 235, 3344, 0.30, 29.7, 660, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2250, '2025-10-08 11:12:14', 1, 8, 7.50, 0.50, 1.50, NULL, 114, 250, 3079, 0.90, 26.2, 734, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2251, '2025-10-07 10:02:14', 1, 8, 7.20, 0.50, 1.90, NULL, 98, 318, 3020, 0.30, 27.6, 700, 'Routine monthly check', '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2252, '2025-10-06 11:59:14', 1, 8, 7.80, 1.90, 1.20, NULL, 111, 236, 3331, 0.60, 27.8, 667, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2253, '2025-10-05 08:11:14', 1, 8, 7.50, 3.00, 2.70, NULL, 93, 376, 3029, 1.00, 26.6, 747, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2254, '2025-10-04 10:33:14', 1, 8, 7.40, 1.70, 2.00, NULL, 116, 369, 3731, 0.50, 28.1, 744, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2255, '2025-10-03 09:27:14', 1, 8, 7.60, 1.80, 3.20, NULL, 83, 334, 3341, 0.60, 30.0, 705, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2256, '2025-10-02 09:42:14', 1, 8, 7.50, 2.20, 3.10, NULL, 103, 318, 3880, 0.70, 27.4, 689, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2257, '2025-10-01 09:03:14', 1, 8, 7.70, 1.30, 3.20, NULL, 105, 324, 3172, 0.90, 29.7, 790, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2258, '2025-09-30 11:30:14', 1, 8, 7.30, 3.00, 2.80, NULL, 115, 224, 3514, 0.30, 29.7, 745, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2259, '2025-09-29 11:41:14', 1, 8, 7.50, 1.30, 2.40, NULL, 94, 300, 3695, 0.30, 26.2, 738, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2260, '2025-09-28 10:17:14', 1, 8, 7.00, 2.80, 1.20, NULL, 83, 307, 3721, 0.40, 26.6, 777, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2261, '2025-09-27 08:07:14', 1, 8, 7.70, 2.80, 1.20, NULL, 108, 206, 3481, 0.70, 28.9, 760, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2262, '2025-09-26 09:25:14', 1, 8, 7.50, 1.20, 1.60, NULL, 108, 389, 3302, 0.50, 26.2, 792, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2263, '2025-09-25 11:08:14', 1, 8, 7.30, 1.10, 2.30, NULL, 82, 398, 3895, 0.40, 29.4, 794, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2264, '2025-09-24 11:12:14', 1, 8, 7.70, 2.40, 0.90, NULL, 112, 208, 3713, 0.00, 26.8, 796, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2265, '2025-09-23 09:43:14', 1, 8, 7.30, 0.70, 2.60, NULL, 91, 230, 3954, 0.20, 28.0, 672, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2266, '2025-09-22 09:35:14', 1, 8, 7.70, 1.30, 3.20, NULL, 100, 357, 3824, 0.90, 29.0, 697, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2267, '2025-09-21 11:22:14', 1, 8, 7.30, 1.40, 2.10, NULL, 102, 329, 3015, 0.70, 28.1, 714, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2268, '2025-09-20 09:30:14', 1, 8, 7.40, 0.70, 3.00, NULL, 112, 335, 3357, 0.80, 29.0, 712, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2269, '2025-09-19 08:38:14', 1, 8, 7.10, 2.70, 3.20, NULL, 82, 376, 3751, 0.90, 26.4, 761, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2270, '2025-09-18 11:42:14', 1, 8, 7.10, 1.80, 2.50, NULL, 120, 379, 3572, 1.00, 28.9, 677, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2271, '2025-09-17 10:32:14', 1, 8, 7.40, 1.90, 1.40, NULL, 84, 322, 3423, 0.80, 28.7, 772, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2272, '2025-09-16 08:38:14', 1, 8, 7.70, 0.70, 1.20, NULL, 95, 215, 3347, 0.60, 29.1, 672, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2273, '2025-09-15 09:14:14', 1, 8, 7.50, 2.80, 2.80, NULL, 118, 212, 3454, 0.90, 28.8, 781, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2274, '2025-09-14 09:16:14', 1, 8, 7.10, 1.60, 3.50, NULL, 85, 262, 3053, 0.00, 26.3, 723, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2275, '2025-09-13 11:26:14', 1, 8, 7.40, 1.40, 1.10, NULL, 102, 307, 3330, 0.20, 26.5, 744, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2276, '2025-09-12 10:11:14', 1, 8, 7.40, 0.70, 2.30, NULL, 89, 323, 3282, 0.40, 27.4, 739, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2277, '2025-09-11 11:51:14', 1, 8, 7.80, 1.40, 1.80, NULL, 120, 299, 3969, 0.60, 27.3, 788, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2278, '2025-09-10 09:05:14', 1, 8, 7.50, 1.30, 1.20, NULL, 118, 365, 3673, 1.00, 27.8, 796, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2279, '2025-09-09 10:03:14', 1, 8, 7.40, 1.20, 3.30, NULL, 101, 281, 3474, 1.00, 28.6, 721, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2280, '2025-09-08 09:15:14', 1, 8, 7.60, 0.50, 2.50, NULL, 108, 221, 3385, 0.00, 26.6, 730, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2281, '2025-09-07 08:56:14', 1, 8, 7.00, 2.60, 1.80, NULL, 111, 221, 3758, 0.10, 29.7, 711, 'Routine monthly check', '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2282, '2025-09-06 09:36:14', 1, 8, 7.50, 2.10, 3.40, NULL, 92, 351, 3066, 0.80, 29.6, 679, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2283, '2025-09-05 09:20:14', 1, 8, 7.60, 2.50, 2.00, NULL, 119, 360, 3679, 0.90, 26.5, 725, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2284, '2025-09-04 08:33:14', 1, 8, 7.20, 3.00, 2.50, NULL, 107, 367, 3737, 0.00, 28.8, 741, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2285, '2025-09-03 08:09:14', 1, 8, 7.20, 3.00, 2.80, NULL, 81, 339, 3491, 0.20, 29.4, 709, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2286, '2025-09-02 10:56:14', 1, 8, 7.50, 2.20, 2.40, NULL, 103, 337, 3873, 0.60, 26.5, 734, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2287, '2025-09-01 10:23:14', 1, 8, 7.30, 1.00, 1.50, NULL, 99, 285, 3472, 0.90, 26.4, 685, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2288, '2025-08-31 09:56:14', 1, 8, 7.10, 2.50, 0.60, NULL, 98, 392, 3349, 0.10, 28.0, 706, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2289, '2025-08-30 11:22:14', 1, 8, 7.00, 2.90, 1.10, NULL, 83, 270, 3208, 0.30, 27.0, 710, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2290, '2025-08-29 08:00:14', 1, 8, 7.30, 1.90, 1.80, NULL, 80, 389, 3197, 0.60, 29.7, 784, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2291, '2025-08-28 10:42:14', 1, 8, 7.30, 1.80, 3.20, NULL, 117, 316, 3747, 0.50, 27.7, 674, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2292, '2025-08-27 08:36:14', 1, 8, 7.50, 3.00, 3.10, NULL, 117, 248, 3757, 0.20, 26.9, 726, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2293, '2025-08-26 11:28:14', 1, 8, 7.70, 1.60, 1.20, NULL, 89, 301, 3812, 0.70, 26.7, 750, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2294, '2025-08-25 11:07:14', 1, 8, 7.10, 2.60, 1.30, NULL, 97, 201, 3569, 1.00, 29.7, 676, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2295, '2025-08-24 08:17:14', 1, 8, 7.60, 2.70, 0.50, NULL, 88, 202, 3073, 0.70, 29.8, 733, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2296, '2025-08-23 11:58:14', 1, 8, 7.50, 2.90, 0.80, NULL, 94, 314, 3902, 0.40, 29.0, 782, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2297, '2025-08-22 09:05:14', 1, 8, 7.10, 2.20, 3.50, NULL, 110, 311, 3092, 0.40, 27.0, 774, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2298, '2025-08-21 11:40:14', 1, 8, 7.30, 1.60, 1.20, NULL, 118, 377, 3164, 0.30, 29.4, 743, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2299, '2025-08-20 11:06:14', 1, 8, 7.30, 0.90, 2.70, NULL, 101, 400, 3395, 0.50, 27.3, 712, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2300, '2025-08-19 08:25:14', 1, 8, 7.40, 1.20, 2.60, NULL, 105, 318, 3589, 0.00, 26.8, 668, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2301, '2025-08-18 10:41:14', 1, 8, 7.60, 1.80, 2.90, NULL, 117, 224, 3422, 0.70, 27.0, 660, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2302, '2025-08-17 11:57:14', 1, 8, 7.10, 2.30, 1.40, NULL, 106, 221, 3359, 0.20, 27.7, 697, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2303, '2025-08-16 10:08:14', 1, 8, 7.50, 0.60, 2.90, NULL, 118, 211, 3101, 0.20, 28.2, 736, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2304, '2025-08-15 10:38:14', 1, 8, 7.50, 1.80, 0.60, NULL, 82, 239, 3222, 0.50, 29.0, 765, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2305, '2025-08-14 11:27:14', 1, 8, 7.70, 1.50, 1.60, NULL, 104, 228, 3771, 0.20, 27.3, 747, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2306, '2025-08-13 09:35:14', 1, 8, 7.20, 0.80, 1.80, NULL, 116, 324, 3232, 0.30, 26.1, 787, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2307, '2025-08-12 08:05:14', 1, 8, 7.20, 0.80, 0.70, NULL, 115, 215, 3179, 0.70, 26.3, 723, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2308, '2025-08-11 11:29:14', 1, 8, 7.50, 3.00, 2.80, NULL, 88, 256, 3468, 0.60, 27.9, 686, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2309, '2025-08-10 11:45:14', 1, 8, 7.50, 2.30, 2.90, NULL, 91, 347, 3072, 0.20, 27.5, 774, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2310, '2025-08-09 10:00:14', 1, 8, 7.60, 1.70, 3.40, NULL, 103, 374, 3052, 0.80, 27.6, 661, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2311, '2025-08-08 10:06:14', 1, 8, 7.60, 1.20, 3.00, NULL, 93, 226, 3700, 0.30, 26.6, 780, 'Routine monthly check', '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2312, '2025-08-07 08:16:14', 1, 8, 7.70, 0.50, 3.30, NULL, 117, 230, 3278, 0.10, 29.0, 662, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2313, '2025-08-06 10:12:14', 1, 8, 7.60, 2.60, 3.40, NULL, 97, 205, 3446, 0.80, 26.4, 682, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2314, '2025-08-05 09:51:14', 1, 8, 7.20, 1.00, 3.20, NULL, 105, 245, 3539, 0.50, 26.7, 654, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2315, '2025-08-04 08:58:14', 1, 8, 7.00, 0.80, 2.20, NULL, 119, 399, 3293, 0.20, 26.3, 798, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2316, '2025-08-03 09:21:14', 1, 8, 7.70, 1.10, 2.90, NULL, 89, 257, 3066, 0.20, 27.4, 663, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2317, '2025-08-02 09:35:14', 1, 8, 7.80, 2.90, 2.40, NULL, 101, 338, 3012, 0.60, 26.9, 693, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2318, '2025-08-01 10:40:14', 1, 8, 7.50, 0.50, 0.80, NULL, 111, 206, 3663, 0.10, 27.7, 697, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2319, '2025-07-31 09:05:14', 1, 8, 7.20, 1.30, 2.90, NULL, 119, 343, 3462, 1.00, 28.6, 752, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2320, '2025-07-30 11:29:14', 1, 8, 7.40, 0.90, 0.60, NULL, 120, 382, 3373, 0.30, 27.0, 757, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2321, '2025-07-29 08:17:14', 1, 8, 7.00, 0.50, 2.50, NULL, 96, 271, 3434, 0.10, 29.3, 684, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2322, '2025-07-28 11:11:14', 1, 8, 7.50, 2.80, 1.00, NULL, 117, 344, 3227, 0.60, 28.4, 792, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2323, '2025-07-27 08:11:14', 1, 8, 7.50, 1.70, 1.40, NULL, 111, 341, 3886, 0.60, 29.7, 769, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2324, '2025-07-26 09:57:14', 1, 8, 7.80, 2.90, 3.30, NULL, 106, 270, 3925, 0.20, 29.6, 793, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2325, '2025-07-25 11:32:14', 1, 8, 7.50, 2.50, 3.10, NULL, 119, 259, 3930, 0.20, 26.8, 667, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2326, '2025-07-24 08:18:14', 1, 8, 7.10, 2.70, 2.90, NULL, 83, 356, 3153, 0.90, 27.0, 721, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2327, '2025-07-23 10:33:14', 1, 8, 7.70, 0.90, 2.80, NULL, 110, 216, 3521, 0.20, 29.7, 697, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2328, '2025-07-22 08:53:14', 1, 8, 7.40, 1.40, 1.50, NULL, 88, 267, 3432, 0.50, 29.7, 706, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2329, '2025-07-21 11:45:14', 1, 8, 7.00, 2.30, 3.30, NULL, 91, 291, 3388, 0.30, 26.5, 713, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2330, '2025-07-20 10:02:14', 1, 8, 7.20, 1.50, 1.90, NULL, 88, 339, 3996, 0.30, 27.1, 676, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2331, '2025-07-19 08:09:14', 1, 8, 7.30, 0.70, 0.50, NULL, 84, 391, 3540, 0.00, 27.7, 791, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2332, '2025-07-18 09:10:14', 1, 8, 7.10, 2.10, 1.00, NULL, 120, 291, 3620, 0.20, 27.4, 659, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2333, '2025-07-17 08:42:14', 1, 8, 7.50, 2.60, 2.30, NULL, 105, 361, 3416, 0.80, 26.5, 772, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2334, '2025-07-16 10:28:14', 1, 8, 7.00, 1.00, 0.70, NULL, 90, 374, 3772, 0.10, 28.1, 731, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2335, '2025-07-15 10:00:14', 1, 8, 7.30, 2.30, 2.00, NULL, 112, 330, 3173, 0.30, 30.0, 680, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2336, '2025-07-14 09:22:14', 1, 8, 7.60, 1.90, 0.50, NULL, 108, 316, 3575, 0.00, 27.1, 697, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2337, '2025-07-13 10:49:14', 1, 8, 7.00, 2.40, 2.60, NULL, 89, 338, 3288, 0.10, 27.9, 695, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2338, '2025-07-12 10:57:14', 1, 8, 7.20, 2.80, 3.40, NULL, 91, 360, 3671, 0.30, 28.2, 739, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2339, '2025-07-11 09:52:14', 1, 8, 7.50, 3.00, 1.20, NULL, 98, 220, 3059, 1.00, 26.8, 739, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2340, '2025-07-10 11:10:14', 1, 8, 7.60, 2.50, 0.80, NULL, 83, 218, 3233, 0.60, 27.9, 719, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2341, '2025-07-09 09:33:14', 1, 8, 7.80, 0.50, 3.30, NULL, 101, 279, 3289, 0.60, 27.7, 747, 'Routine monthly check', '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2342, '2025-07-08 11:43:14', 1, 8, 7.10, 2.50, 2.60, NULL, 106, 219, 3256, 0.20, 28.5, 759, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2343, '2025-07-07 08:04:14', 1, 8, 7.10, 2.80, 3.30, NULL, 86, 216, 3002, 0.90, 27.5, 650, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2344, '2025-07-06 09:21:14', 1, 8, 7.00, 1.60, 2.60, NULL, 112, 276, 3797, 0.90, 28.9, 791, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2345, '2025-07-05 08:33:14', 1, 8, 7.00, 1.30, 1.40, NULL, 117, 295, 3389, 0.60, 27.9, 774, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2346, '2025-07-04 08:24:14', 1, 8, 7.70, 1.00, 1.90, NULL, 94, 237, 3243, 0.80, 29.2, 712, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2347, '2025-07-03 11:27:14', 1, 8, 7.50, 0.70, 3.10, NULL, 80, 308, 3914, 0.00, 27.7, 688, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2348, '2025-07-02 08:48:14', 1, 8, 7.60, 1.60, 1.90, NULL, 115, 234, 3048, 0.00, 29.4, 770, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2349, '2025-07-01 10:25:14', 1, 8, 7.30, 2.70, 1.20, NULL, 81, 289, 3849, 1.00, 29.2, 655, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2350, '2025-06-30 09:45:14', 1, 8, 7.20, 2.00, 1.30, NULL, 103, 266, 3291, 0.00, 29.6, 698, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2351, '2025-06-29 11:45:14', 1, 8, 7.00, 2.70, 3.20, NULL, 102, 331, 3716, 0.70, 26.3, 722, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2352, '2025-06-28 08:11:14', 1, 8, 7.00, 2.00, 1.00, NULL, 95, 252, 3482, 0.60, 28.9, 710, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2353, '2025-06-27 11:29:14', 1, 8, 7.80, 2.30, 2.50, NULL, 91, 231, 3694, 0.60, 28.5, 790, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2354, '2025-06-26 09:41:14', 1, 8, 7.10, 2.10, 1.80, NULL, 81, 374, 3148, 0.10, 26.2, 717, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2355, '2025-06-25 11:39:14', 1, 8, 7.50, 2.80, 2.40, NULL, 117, 215, 3814, 0.40, 27.2, 749, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2356, '2025-06-24 09:18:14', 1, 8, 7.00, 0.80, 2.70, NULL, 90, 314, 3595, 0.20, 26.5, 712, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2357, '2025-06-23 10:37:14', 1, 8, 7.80, 1.30, 1.90, NULL, 114, 278, 3122, 0.00, 27.9, 741, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2358, '2025-06-22 10:07:14', 1, 8, 7.60, 2.30, 1.10, NULL, 91, 352, 3975, 0.80, 26.1, 799, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2359, '2025-06-21 08:50:14', 1, 8, 7.10, 2.60, 2.20, NULL, 120, 268, 3518, 0.00, 29.1, 655, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2360, '2025-06-20 08:14:14', 1, 8, 7.70, 0.70, 1.40, NULL, 113, 296, 3766, 0.60, 30.0, 727, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2361, '2025-06-19 08:14:14', 1, 8, 7.10, 0.60, 1.10, NULL, 120, 263, 3823, 0.60, 26.9, 680, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2362, '2025-06-18 10:34:14', 1, 8, 7.70, 1.10, 3.40, NULL, 101, 226, 3803, 0.00, 26.3, 653, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2363, '2025-06-17 08:54:14', 1, 8, 7.10, 2.20, 2.80, NULL, 103, 353, 3623, 1.00, 29.9, 777, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2364, '2025-06-16 08:28:14', 1, 8, 7.00, 1.30, 1.00, NULL, 98, 312, 3802, 0.30, 26.8, 687, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2365, '2025-06-15 11:23:14', 1, 8, 7.40, 2.20, 3.00, NULL, 112, 243, 3825, 0.40, 29.3, 707, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2366, '2025-06-14 09:43:14', 1, 8, 7.70, 1.30, 1.20, NULL, 92, 270, 3337, 0.50, 27.6, 686, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2367, '2025-06-13 11:05:14', 1, 8, 7.10, 1.00, 0.70, NULL, 83, 372, 3643, 0.80, 27.1, 775, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2368, '2025-06-12 10:42:14', 1, 8, 7.80, 1.80, 3.40, NULL, 95, 372, 3759, 0.10, 27.9, 729, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2369, '2025-06-11 11:56:14', 1, 8, 7.70, 2.50, 2.40, NULL, 95, 378, 3160, 0.20, 28.8, 680, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2370, '2025-06-10 10:56:14', 1, 8, 7.60, 1.90, 1.10, NULL, 105, 390, 3689, 0.00, 29.8, 680, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2371, '2025-06-09 09:02:14', 1, 8, 7.50, 0.60, 1.70, NULL, 117, 394, 3545, 0.10, 26.3, 741, 'Routine monthly check', '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2372, '2025-06-08 08:00:14', 1, 8, 7.20, 1.80, 1.30, NULL, 97, 316, 3910, 0.00, 28.0, 730, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2373, '2025-06-07 11:46:14', 1, 8, 7.60, 2.20, 2.70, NULL, 106, 279, 3721, 0.30, 27.3, 663, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2374, '2025-06-06 10:43:14', 1, 8, 7.20, 2.20, 2.30, NULL, 86, 364, 3881, 0.00, 26.3, 751, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2375, '2025-06-05 09:14:14', 1, 8, 7.30, 2.30, 3.50, NULL, 91, 240, 3105, 0.30, 28.2, 756, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2376, '2025-06-04 09:13:14', 1, 8, 7.00, 0.70, 2.80, NULL, 116, 364, 3598, 1.00, 27.3, 738, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2377, '2025-06-03 10:19:14', 1, 8, 7.60, 0.60, 2.60, NULL, 111, 227, 3051, 0.40, 28.3, 679, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2378, '2025-06-02 11:43:14', 1, 8, 7.40, 2.60, 0.60, NULL, 104, 328, 3802, 1.00, 30.0, 689, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2379, '2025-06-01 10:52:14', 1, 8, 7.10, 1.50, 1.30, NULL, 113, 274, 3935, 0.40, 27.7, 714, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2380, '2025-05-31 08:27:14', 1, 8, 7.60, 1.70, 2.90, NULL, 83, 348, 3810, 0.00, 28.5, 659, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2381, '2025-05-30 10:11:14', 1, 8, 7.70, 0.80, 1.00, NULL, 111, 345, 3941, 0.60, 28.0, 759, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2382, '2025-05-29 09:07:14', 1, 8, 7.70, 2.20, 2.10, NULL, 114, 210, 3862, 0.00, 26.4, 655, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2383, '2025-05-28 10:55:14', 1, 8, 7.00, 2.80, 1.00, NULL, 96, 390, 3122, 0.70, 29.7, 693, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2384, '2025-05-27 10:52:14', 1, 8, 7.70, 1.50, 1.60, NULL, 90, 364, 3609, 0.90, 26.2, 663, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2385, '2025-05-26 10:25:14', 1, 8, 7.20, 1.30, 1.90, NULL, 86, 347, 3744, 0.30, 29.6, 745, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2386, '2025-05-25 09:13:14', 1, 8, 7.10, 0.70, 2.80, NULL, 115, 283, 3673, 0.50, 28.0, 757, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2387, '2025-05-24 11:26:14', 1, 8, 7.00, 1.50, 0.50, NULL, 89, 379, 3887, 0.90, 26.8, 708, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2388, '2025-05-23 08:08:14', 1, 8, 7.30, 0.50, 2.00, NULL, 94, 350, 3732, 0.10, 27.5, 795, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2389, '2025-05-22 09:21:14', 1, 8, 7.70, 1.50, 1.70, NULL, 105, 328, 3955, 0.50, 26.8, 671, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2390, '2025-05-21 11:53:14', 1, 8, 7.40, 2.00, 1.40, NULL, 97, 338, 3758, 0.90, 30.0, 764, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2391, '2025-05-20 08:56:14', 1, 8, 7.40, 2.50, 1.00, NULL, 117, 364, 3644, 0.80, 27.2, 799, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2392, '2025-05-19 09:36:14', 1, 8, 7.00, 3.00, 3.40, NULL, 100, 348, 3540, 0.90, 26.2, 701, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2393, '2025-05-18 08:00:14', 1, 8, 7.30, 0.50, 2.90, NULL, 116, 359, 3374, 0.70, 26.6, 771, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2394, '2025-05-17 11:03:14', 1, 8, 7.30, 1.30, 1.80, NULL, 99, 343, 3665, 0.30, 29.7, 732, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2395, '2025-05-16 09:07:14', 1, 8, 7.20, 1.30, 1.10, NULL, 98, 336, 3537, 0.00, 26.7, 715, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2396, '2025-05-15 09:35:14', 1, 8, 7.50, 2.20, 3.00, NULL, 109, 323, 3313, 0.60, 27.8, 670, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2397, '2025-05-14 09:55:14', 1, 8, 7.00, 2.50, 0.90, NULL, 83, 377, 3140, 0.60, 29.9, 742, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2398, '2025-05-13 11:00:14', 1, 8, 7.70, 2.40, 0.80, NULL, 115, 295, 3612, 0.10, 27.6, 692, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2399, '2025-05-12 09:03:14', 1, 8, 7.40, 1.30, 1.20, NULL, 98, 398, 3586, 0.30, 29.0, 725, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2400, '2025-05-11 09:13:14', 1, 8, 7.20, 0.90, 1.30, NULL, 89, 209, 3657, 0.60, 29.2, 668, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2401, '2025-05-10 09:23:14', 1, 8, 7.00, 1.70, 2.70, NULL, 94, 390, 3483, 0.00, 29.9, 779, 'Routine monthly check', '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2402, '2025-05-09 09:52:14', 1, 8, 7.70, 1.70, 2.00, NULL, 108, 301, 3266, 0.40, 27.3, 659, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2403, '2025-05-08 08:58:14', 1, 8, 7.20, 2.40, 1.20, NULL, 90, 348, 3559, 0.20, 26.4, 799, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2404, '2025-05-07 09:14:14', 1, 8, 7.70, 2.50, 1.10, NULL, 106, 230, 3341, 0.20, 29.8, 658, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2405, '2025-05-06 09:25:14', 1, 8, 7.30, 1.40, 2.70, NULL, 107, 216, 3365, 0.80, 26.8, 790, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2406, '2025-05-05 09:55:14', 1, 8, 7.00, 2.80, 3.30, NULL, 91, 311, 3543, 0.10, 27.4, 774, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2407, '2025-05-04 08:12:14', 1, 8, 7.00, 1.10, 1.80, NULL, 90, 302, 3746, 0.00, 27.0, 797, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2408, '2025-05-03 11:52:14', 1, 8, 7.80, 3.00, 3.40, NULL, 94, 332, 3383, 0.20, 28.6, 666, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2409, '2025-05-02 08:28:14', 1, 8, 7.10, 1.20, 1.10, NULL, 89, 303, 3899, 0.10, 28.9, 697, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2410, '2025-05-01 09:12:14', 1, 8, 7.80, 2.30, 1.20, NULL, 116, 223, 3461, 0.80, 29.8, 695, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2411, '2025-04-30 11:23:14', 1, 8, 7.70, 2.00, 3.40, NULL, 104, 335, 3553, 0.00, 30.0, 743, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2412, '2025-04-29 09:21:14', 1, 8, 7.00, 0.80, 1.70, NULL, 86, 234, 3680, 1.00, 29.4, 716, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2413, '2025-04-28 09:49:14', 1, 8, 7.50, 2.20, 2.30, NULL, 97, 319, 3163, 0.70, 29.1, 653, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2414, '2025-04-27 10:02:14', 1, 8, 7.00, 0.60, 3.20, NULL, 113, 272, 3706, 0.00, 28.7, 672, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2415, '2025-04-26 11:56:14', 1, 8, 7.70, 2.00, 2.40, NULL, 119, 226, 3341, 0.20, 29.8, 716, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2416, '2025-04-25 11:09:14', 1, 8, 7.60, 2.90, 1.60, NULL, 116, 352, 3388, 1.00, 29.6, 758, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2417, '2025-04-24 09:37:14', 1, 8, 7.80, 0.90, 2.60, NULL, 101, 337, 3775, 0.60, 28.3, 753, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2418, '2025-04-23 11:23:14', 1, 8, 7.60, 1.10, 0.60, NULL, 80, 362, 3408, 0.00, 29.1, 711, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2419, '2025-04-22 10:05:14', 1, 8, 7.60, 1.90, 3.10, NULL, 92, 218, 3333, 0.00, 28.9, 732, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2420, '2025-04-21 11:41:14', 1, 8, 7.60, 0.90, 3.10, NULL, 109, 395, 3207, 0.70, 28.1, 652, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2421, '2025-04-20 08:39:14', 1, 8, 7.50, 0.80, 2.10, NULL, 95, 275, 3402, 0.70, 27.4, 736, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2422, '2025-04-19 09:49:14', 1, 8, 7.20, 2.70, 1.30, NULL, 115, 204, 3884, 0.40, 27.0, 683, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2423, '2025-04-18 09:33:14', 1, 8, 7.40, 1.00, 2.40, NULL, 117, 279, 3014, 0.40, 27.6, 691, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2424, '2025-04-17 11:40:14', 1, 8, 7.60, 2.50, 2.30, NULL, 112, 319, 3964, 0.40, 29.6, 665, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2425, '2025-04-16 09:41:14', 1, 8, 7.40, 2.60, 2.00, NULL, 113, 291, 3394, 0.00, 27.5, 730, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2426, '2025-04-15 10:22:14', 1, 8, 7.10, 2.30, 3.40, NULL, 113, 393, 3537, 0.60, 26.8, 695, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2427, '2025-04-14 11:33:14', 1, 8, 7.20, 1.40, 3.50, NULL, 100, 306, 3459, 0.80, 26.8, 787, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2428, '2025-04-13 10:02:14', 1, 8, 7.50, 2.30, 2.00, NULL, 114, 323, 3924, 0.30, 27.3, 722, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2429, '2025-04-12 10:37:14', 1, 8, 7.10, 2.10, 2.00, NULL, 84, 295, 3789, 0.90, 28.7, 770, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2430, '2025-04-11 11:18:14', 1, 8, 7.80, 1.60, 0.70, NULL, 96, 273, 3479, 0.30, 28.4, 650, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2431, '2025-04-10 10:44:14', 1, 8, 7.50, 0.90, 3.00, NULL, 83, 275, 3017, 0.20, 27.7, 670, 'Routine monthly check', '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2432, '2025-04-09 11:22:14', 1, 8, 7.10, 3.00, 1.80, NULL, 90, 268, 3056, 0.90, 30.0, 799, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2433, '2025-04-08 09:57:14', 1, 8, 7.80, 2.60, 1.60, NULL, 117, 223, 3939, 1.00, 29.6, 732, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2434, '2025-04-07 09:22:14', 1, 8, 7.30, 0.50, 0.50, NULL, 114, 396, 3351, 1.00, 28.4, 652, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2435, '2025-04-06 10:30:14', 1, 8, 7.00, 1.80, 3.20, NULL, 115, 321, 3568, 0.50, 26.8, 793, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2436, '2025-04-05 10:29:14', 1, 8, 7.00, 0.70, 1.40, NULL, 103, 376, 3069, 0.30, 28.3, 660, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2437, '2025-04-04 08:42:14', 1, 8, 7.20, 2.50, 1.40, NULL, 100, 359, 3848, 0.00, 27.5, 704, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2438, '2025-04-03 10:22:14', 1, 8, 7.20, 1.10, 1.80, NULL, 87, 201, 3367, 0.90, 29.7, 695, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2439, '2025-04-02 10:32:14', 1, 8, 7.80, 2.90, 0.50, NULL, 109, 326, 3802, 0.60, 29.2, 706, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2440, '2025-04-01 09:43:14', 1, 8, 7.00, 0.80, 3.30, NULL, 105, 307, 3880, 0.60, 29.2, 732, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2441, '2025-03-31 09:12:14', 1, 8, 7.00, 1.60, 1.90, NULL, 95, 298, 3091, 0.60, 29.5, 722, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2442, '2025-03-30 09:12:14', 1, 8, 7.70, 1.00, 2.10, NULL, 113, 233, 3097, 0.00, 28.3, 723, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2443, '2025-03-29 08:45:14', 1, 8, 7.00, 2.60, 1.10, NULL, 95, 390, 3669, 0.10, 27.4, 796, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2444, '2025-03-28 10:06:14', 1, 8, 7.40, 1.30, 2.40, NULL, 85, 209, 3688, 0.40, 26.0, 789, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2445, '2025-03-27 08:32:14', 1, 8, 7.00, 1.00, 1.70, NULL, 111, 273, 3677, 0.50, 27.6, 741, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2446, '2025-03-26 10:19:14', 1, 8, 7.40, 0.50, 1.40, NULL, 87, 243, 3657, 0.20, 29.4, 693, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2447, '2025-03-25 10:26:14', 1, 8, 7.60, 0.60, 1.00, NULL, 104, 302, 3881, 0.50, 27.0, 690, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2448, '2025-03-24 08:22:14', 1, 8, 7.20, 1.70, 1.10, NULL, 112, 206, 3250, 0.60, 28.9, 658, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2449, '2025-03-23 09:14:14', 1, 8, 7.80, 1.80, 1.60, NULL, 90, 379, 3924, 0.00, 27.9, 739, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2450, '2025-03-22 09:12:14', 1, 8, 7.70, 1.10, 1.30, NULL, 96, 240, 3859, 0.70, 27.0, 663, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2451, '2025-03-21 09:08:14', 1, 8, 7.70, 1.00, 3.40, NULL, 84, 352, 3717, 0.60, 28.1, 754, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2452, '2025-03-20 11:05:14', 1, 8, 7.50, 1.30, 2.70, NULL, 102, 209, 3081, 0.60, 29.7, 686, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2453, '2025-03-19 10:07:14', 1, 8, 7.00, 2.30, 1.70, NULL, 114, 350, 3263, 0.80, 27.0, 737, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2454, '2025-03-18 11:13:14', 1, 8, 7.60, 1.90, 1.90, NULL, 117, 282, 3307, 0.00, 28.9, 728, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2455, '2025-03-17 11:04:14', 1, 8, 7.10, 0.70, 0.60, NULL, 105, 299, 3861, 0.30, 26.0, 671, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2456, '2025-03-16 11:48:14', 1, 8, 7.50, 2.00, 1.70, NULL, 115, 216, 3815, 0.10, 27.8, 686, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2457, '2025-03-15 09:09:14', 1, 8, 7.10, 2.20, 2.50, NULL, 117, 315, 3128, 0.70, 26.2, 661, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2458, '2025-03-14 11:02:14', 1, 8, 7.80, 1.60, 0.90, NULL, 98, 286, 3446, 0.30, 26.4, 675, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2459, '2025-03-13 09:44:14', 1, 8, 7.60, 2.40, 2.30, NULL, 88, 210, 3154, 0.80, 27.8, 704, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2460, '2025-03-12 11:25:14', 1, 8, 7.10, 0.70, 2.00, NULL, 113, 309, 3243, 0.10, 26.8, 749, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2461, '2025-03-11 11:01:14', 1, 8, 7.30, 1.30, 2.60, NULL, 119, 219, 3424, 0.90, 26.3, 678, 'Routine monthly check', '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2462, '2025-03-10 08:00:14', 1, 8, 7.00, 1.10, 0.60, NULL, 112, 347, 3344, 0.20, 29.8, 683, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2463, '2025-03-09 10:16:14', 1, 8, 7.80, 1.90, 1.70, NULL, 116, 371, 3891, 0.80, 29.4, 723, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2464, '2025-03-08 10:13:14', 1, 8, 7.50, 2.80, 2.80, NULL, 120, 378, 3745, 0.50, 27.8, 727, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2465, '2025-03-07 08:21:14', 1, 8, 7.80, 0.70, 1.00, NULL, 98, 385, 3105, 0.40, 27.6, 664, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2466, '2025-03-06 08:07:14', 1, 8, 7.10, 2.20, 2.60, NULL, 106, 352, 3238, 0.80, 26.1, 750, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2467, '2025-03-05 09:40:14', 1, 8, 7.20, 2.20, 1.40, NULL, 115, 256, 3702, 0.40, 27.7, 685, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2468, '2025-03-04 10:49:14', 1, 8, 7.70, 1.50, 2.60, NULL, 92, 375, 3738, 0.40, 26.0, 758, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2469, '2025-03-03 10:23:14', 1, 8, 7.20, 2.20, 2.40, NULL, 119, 268, 3504, 0.70, 27.4, 759, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2470, '2025-03-02 08:39:14', 1, 8, 7.60, 2.80, 0.60, NULL, 86, 216, 3415, 0.60, 29.9, 730, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2471, '2025-03-01 10:52:14', 1, 8, 7.70, 0.90, 3.40, NULL, 89, 330, 3641, 0.10, 27.1, 775, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2472, '2025-02-28 08:58:14', 1, 8, 7.10, 0.80, 2.60, NULL, 97, 215, 3590, 0.80, 27.5, 713, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2473, '2025-02-27 08:54:14', 1, 8, 7.30, 1.00, 3.30, NULL, 88, 323, 3604, 0.70, 26.2, 794, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2474, '2025-02-26 08:43:14', 1, 8, 7.10, 0.90, 3.10, NULL, 104, 244, 3754, 0.40, 28.1, 735, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2475, '2025-02-25 09:15:14', 1, 8, 7.50, 0.60, 2.20, NULL, 95, 204, 3077, 0.70, 26.8, 800, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2476, '2025-02-24 08:06:14', 1, 8, 7.20, 0.90, 0.70, NULL, 114, 252, 3701, 0.80, 29.1, 732, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2477, '2025-02-23 08:13:14', 1, 8, 7.20, 2.50, 1.20, NULL, 83, 288, 3347, 0.10, 26.9, 773, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2478, '2025-02-22 09:59:14', 1, 8, 7.00, 1.60, 3.20, NULL, 113, 356, 3762, 0.40, 29.6, 764, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2479, '2025-02-21 10:13:14', 1, 8, 7.70, 3.00, 3.40, NULL, 112, 266, 3576, 0.80, 29.7, 759, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2480, '2025-02-20 08:05:14', 1, 8, 7.80, 2.90, 0.80, NULL, 81, 300, 3797, 0.20, 26.7, 737, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2481, '2025-02-19 08:09:14', 1, 8, 7.60, 2.40, 1.90, NULL, 88, 235, 3393, 0.40, 28.5, 687, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2482, '2025-02-18 10:08:14', 1, 8, 7.80, 0.50, 1.10, NULL, 90, 208, 3945, 0.90, 27.1, 708, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2483, '2025-02-17 10:22:14', 1, 8, 7.70, 2.50, 3.10, NULL, 85, 237, 3678, 0.90, 26.2, 799, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2484, '2025-02-16 08:16:14', 1, 8, 7.10, 0.60, 1.70, NULL, 91, 305, 3572, 0.10, 30.0, 684, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2485, '2025-02-15 09:12:14', 1, 8, 7.40, 2.70, 3.50, NULL, 88, 267, 3615, 0.20, 29.5, 726, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2486, '2025-02-14 09:56:14', 1, 8, 7.30, 2.40, 2.00, NULL, 92, 371, 3675, 0.30, 28.6, 671, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2487, '2025-02-13 10:02:14', 1, 8, 7.80, 1.40, 3.50, NULL, 93, 287, 3047, 0.10, 26.8, 686, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2488, '2025-02-12 08:30:14', 1, 8, 7.20, 2.90, 3.40, NULL, 109, 254, 3079, 1.00, 27.9, 778, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2489, '2025-02-11 08:07:14', 1, 8, 7.10, 1.00, 0.60, NULL, 90, 363, 3060, 0.40, 28.0, 729, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2490, '2025-02-10 09:53:14', 1, 8, 7.80, 2.30, 2.00, NULL, 108, 384, 3315, 0.20, 28.6, 754, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2491, '2025-02-09 10:49:14', 1, 8, 7.80, 2.20, 0.70, NULL, 108, 351, 3119, 0.00, 29.6, 702, 'Routine monthly check', '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2492, '2025-02-08 09:03:14', 1, 8, 7.60, 1.00, 1.90, NULL, 100, 217, 3869, 0.60, 26.6, 738, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2493, '2025-02-07 08:59:14', 1, 8, 7.60, 3.00, 2.30, NULL, 119, 243, 3077, 0.60, 27.8, 783, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2494, '2025-02-06 11:59:14', 1, 8, 7.40, 2.60, 2.70, NULL, 99, 400, 3048, 0.90, 28.1, 691, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2495, '2025-02-05 11:16:14', 1, 8, 7.70, 1.20, 1.60, NULL, 103, 332, 3980, 0.90, 27.1, 756, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2496, '2025-02-04 10:31:14', 1, 8, 7.20, 2.90, 1.00, NULL, 95, 310, 3406, 0.60, 26.1, 704, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2497, '2025-02-03 11:12:14', 1, 8, 7.00, 1.80, 3.30, NULL, 96, 295, 3629, 0.70, 27.2, 777, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2498, '2025-02-02 10:15:14', 1, 8, 7.70, 1.60, 3.20, NULL, 117, 246, 3727, 0.90, 29.2, 670, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2499, '2025-02-01 11:39:14', 1, 8, 7.00, 0.60, 1.30, NULL, 85, 399, 3472, 0.10, 27.4, 688, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2500, '2025-01-31 08:46:14', 1, 8, 7.50, 1.60, 1.90, NULL, 97, 213, 3450, 0.50, 28.4, 670, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2501, '2025-01-30 09:43:14', 1, 8, 7.20, 2.00, 1.40, NULL, 91, 300, 3099, 0.20, 26.5, 739, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2502, '2025-01-29 08:16:14', 1, 8, 7.40, 2.70, 2.30, NULL, 119, 230, 3778, 0.30, 29.9, 799, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2503, '2025-01-28 11:43:14', 1, 8, 7.80, 0.80, 0.70, NULL, 84, 298, 3182, 0.50, 28.0, 652, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2504, '2025-01-27 09:24:14', 1, 8, 7.40, 1.90, 2.50, NULL, 117, 366, 3390, 0.60, 28.1, 768, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2505, '2025-01-26 09:51:14', 1, 8, 7.80, 2.20, 1.50, NULL, 109, 251, 3475, 0.00, 27.5, 667, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2506, '2025-01-25 11:00:14', 1, 8, 7.20, 2.50, 1.30, NULL, 97, 285, 3650, 0.70, 29.5, 713, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2507, '2025-01-24 08:06:14', 1, 8, 7.70, 1.20, 0.60, NULL, 88, 312, 3438, 0.70, 29.7, 682, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2508, '2025-01-23 11:55:14', 1, 8, 7.20, 1.80, 3.00, NULL, 95, 329, 3733, 0.20, 30.0, 760, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2509, '2025-01-22 08:43:14', 1, 8, 7.30, 1.20, 2.10, NULL, 80, 260, 3052, 0.70, 29.9, 657, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2510, '2025-01-21 09:18:14', 1, 8, 7.80, 2.50, 3.10, NULL, 112, 267, 3584, 0.20, 28.2, 653, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2511, '2025-01-20 10:19:14', 1, 8, 7.40, 2.20, 2.00, NULL, 93, 281, 3087, 0.70, 29.4, 723, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2512, '2025-01-19 09:21:14', 1, 8, 7.20, 1.90, 2.00, NULL, 106, 247, 3767, 0.30, 26.8, 700, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2513, '2025-01-18 09:26:14', 1, 8, 7.00, 1.70, 2.30, NULL, 83, 352, 3103, 0.00, 28.0, 763, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2514, '2025-01-17 09:37:14', 1, 8, 7.80, 0.60, 0.60, NULL, 85, 343, 3512, 0.10, 29.0, 669, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2515, '2025-01-16 10:30:14', 1, 8, 7.30, 2.50, 1.50, NULL, 97, 345, 3833, 0.00, 27.3, 759, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2516, '2025-01-15 11:21:14', 1, 8, 7.80, 2.00, 2.30, NULL, 93, 267, 3190, 0.80, 26.7, 738, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2517, '2025-01-14 11:47:14', 1, 8, 7.60, 2.80, 0.60, NULL, 104, 249, 3901, 0.50, 26.3, 776, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2518, '2025-01-13 09:52:14', 1, 8, 7.60, 2.60, 0.80, NULL, 84, 256, 3684, 0.90, 29.1, 764, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2519, '2025-01-12 10:56:14', 1, 8, 7.30, 1.10, 3.50, NULL, 82, 368, 3119, 0.00, 27.6, 684, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2520, '2025-01-11 08:06:14', 1, 8, 7.10, 1.10, 1.60, NULL, 104, 336, 3720, 0.80, 27.4, 769, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2521, '2025-01-10 08:47:14', 1, 8, 7.00, 1.70, 2.60, NULL, 101, 296, 3453, 0.60, 27.8, 708, 'Routine monthly check', '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2522, '2025-01-09 09:51:14', 1, 8, 7.30, 1.80, 1.10, NULL, 116, 338, 3486, 0.00, 27.0, 755, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2523, '2025-01-08 09:22:14', 1, 8, 7.40, 0.80, 1.90, NULL, 100, 262, 3171, 0.10, 26.5, 693, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2524, '2025-01-07 10:01:14', 1, 8, 7.40, 2.90, 1.40, NULL, 112, 321, 3986, 0.90, 27.9, 785, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2525, '2025-01-06 11:49:14', 1, 8, 7.50, 2.90, 1.40, NULL, 86, 318, 3505, 0.50, 28.6, 730, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2526, '2025-01-05 08:13:14', 1, 8, 7.30, 0.80, 2.40, NULL, 116, 347, 3747, 0.90, 26.2, 651, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2527, '2025-01-04 10:47:14', 1, 8, 7.20, 2.60, 2.10, NULL, 118, 227, 3778, 0.40, 29.1, 709, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2528, '2025-01-03 09:01:14', 1, 8, 7.70, 1.90, 1.20, NULL, 116, 357, 3986, 0.80, 26.2, 760, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2529, '2025-01-02 08:58:14', 1, 8, 7.60, 2.70, 2.30, NULL, 104, 388, 3412, 0.30, 28.1, 675, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2530, '2025-01-01 10:21:14', 1, 8, 7.70, 3.00, 2.40, NULL, 88, 301, 3430, 0.60, 29.2, 686, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2531, '2024-12-31 10:07:14', 1, 8, 7.20, 2.70, 2.60, NULL, 101, 389, 3358, 0.20, 27.2, 752, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2532, '2024-12-30 08:30:14', 1, 8, 7.70, 2.60, 0.60, NULL, 85, 297, 3884, 0.50, 28.4, 691, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2533, '2024-12-29 09:00:14', 1, 8, 7.20, 1.20, 3.50, NULL, 81, 291, 3765, 0.30, 28.5, 798, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2534, '2024-12-28 10:58:14', 1, 8, 7.80, 2.50, 0.90, NULL, 96, 239, 3690, 1.00, 26.6, 660, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2535, '2024-12-27 10:35:14', 1, 8, 7.30, 3.00, 2.20, NULL, 82, 293, 3247, 0.90, 27.7, 739, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2536, '2024-12-26 08:05:14', 1, 8, 7.70, 2.40, 3.00, NULL, 92, 301, 3506, 0.10, 28.2, 734, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2537, '2024-12-25 10:37:14', 1, 8, 7.30, 2.80, 3.40, NULL, 119, 383, 3325, 0.60, 28.4, 725, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2538, '2024-12-24 11:07:14', 1, 8, 7.20, 2.10, 2.10, NULL, 115, 360, 3118, 0.10, 27.9, 798, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2539, '2024-12-23 08:33:14', 1, 8, 7.70, 2.10, 3.30, NULL, 92, 321, 3656, 1.00, 28.9, 669, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2540, '2024-12-22 09:26:14', 1, 8, 7.80, 1.10, 3.50, NULL, 108, 262, 3846, 0.60, 27.4, 674, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2541, '2024-12-21 09:00:14', 1, 8, 7.30, 2.00, 2.50, NULL, 99, 398, 3959, 0.70, 28.4, 714, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2542, '2024-12-20 10:49:14', 1, 8, 7.30, 1.00, 0.70, NULL, 96, 279, 3471, 0.80, 27.0, 712, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2543, '2024-12-19 11:50:14', 1, 8, 7.80, 1.10, 2.30, NULL, 81, 318, 3898, 0.30, 28.8, 721, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2544, '2024-12-18 09:03:14', 1, 8, 7.40, 2.10, 0.60, NULL, 88, 253, 3877, 0.60, 28.5, 667, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2545, '2024-12-17 11:25:14', 1, 8, 7.10, 2.60, 1.20, NULL, 93, 322, 3717, 0.30, 28.2, 765, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2546, '2024-12-16 11:22:14', 1, 8, 7.40, 0.90, 1.00, NULL, 83, 300, 3433, 0.00, 26.1, 654, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2547, '2024-12-15 08:35:14', 1, 8, 7.40, 1.00, 0.80, NULL, 89, 207, 3008, 0.60, 26.6, 721, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2548, '2024-12-14 08:24:14', 1, 8, 7.60, 2.60, 3.10, NULL, 115, 376, 3925, 0.50, 29.0, 686, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2549, '2024-12-13 09:17:14', 1, 8, 7.50, 0.50, 0.70, NULL, 80, 234, 3967, 0.70, 29.9, 700, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2550, '2024-12-12 11:37:14', 1, 8, 7.00, 2.70, 2.60, NULL, 107, 332, 3855, 0.50, 27.2, 782, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2551, '2024-12-11 08:07:14', 1, 8, 7.20, 1.90, 1.00, NULL, 109, 212, 3698, 0.10, 26.9, 701, 'Routine monthly check', '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2552, '2024-12-10 09:37:14', 1, 8, 7.00, 2.90, 1.70, NULL, 86, 234, 3607, 0.50, 29.8, 757, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2553, '2024-12-09 11:54:14', 1, 8, 7.20, 3.00, 2.50, NULL, 84, 268, 3972, 0.10, 27.4, 796, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2554, '2024-12-08 11:20:14', 1, 8, 7.60, 2.00, 1.90, NULL, 111, 316, 3470, 0.60, 27.8, 777, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2555, '2024-12-07 10:37:14', 1, 8, 7.60, 2.10, 1.70, NULL, 112, 305, 3557, 1.00, 28.5, 713, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2556, '2025-12-06 11:37:14', 1, 9, 7.70, 2.20, 1.40, NULL, 118, 347, 3660, 0.20, 29.3, 761, 'Routine monthly check', '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2557, '2025-12-05 11:06:14', 1, 9, 7.30, 0.50, 2.20, NULL, 110, 329, 3135, 0.50, 29.6, 779, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2558, '2025-12-04 09:18:14', 1, 9, 7.40, 0.80, 3.20, NULL, 117, 262, 3184, 0.50, 27.1, 682, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2559, '2025-12-03 11:15:14', 1, 9, 7.30, 2.20, 0.90, NULL, 111, 328, 3981, 0.30, 29.1, 717, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2560, '2025-12-02 08:57:14', 1, 9, 7.50, 2.10, 3.40, NULL, 113, 380, 3271, 1.00, 27.7, 764, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2561, '2025-12-01 11:26:14', 1, 9, 7.50, 2.30, 3.20, NULL, 89, 225, 3147, 0.00, 28.8, 706, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2562, '2025-11-30 11:10:14', 1, 9, 7.00, 2.90, 1.70, NULL, 111, 245, 3163, 0.80, 28.3, 732, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2563, '2025-11-29 11:02:14', 1, 9, 7.00, 2.20, 2.90, NULL, 98, 317, 3170, 0.70, 29.9, 799, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2564, '2025-11-28 09:53:14', 1, 9, 7.30, 0.50, 1.70, NULL, 88, 382, 3473, 0.70, 27.2, 798, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2565, '2025-11-27 10:17:14', 1, 9, 7.40, 2.90, 0.60, NULL, 119, 354, 3944, 0.00, 27.7, 671, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2566, '2025-11-26 11:10:14', 1, 9, 7.10, 1.70, 2.60, NULL, 98, 397, 3486, 0.00, 30.0, 716, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2567, '2025-11-25 11:55:14', 1, 9, 7.10, 1.30, 1.80, NULL, 92, 288, 3942, 0.50, 28.6, 662, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2568, '2025-11-24 11:28:14', 1, 9, 7.50, 2.60, 1.80, NULL, 89, 354, 3436, 0.00, 26.9, 773, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2569, '2025-11-23 08:46:14', 1, 9, 7.80, 0.90, 3.30, NULL, 117, 222, 3343, 0.80, 29.9, 725, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2570, '2025-11-22 10:26:14', 1, 9, 7.30, 1.20, 3.20, NULL, 115, 362, 3023, 0.20, 26.1, 717, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2571, '2025-11-21 08:57:14', 1, 9, 7.50, 0.80, 2.70, NULL, 114, 396, 3663, 0.80, 27.5, 743, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2572, '2025-11-20 10:06:14', 1, 9, 7.20, 1.80, 0.90, NULL, 103, 221, 3046, 0.80, 28.2, 728, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2573, '2025-11-19 09:59:14', 1, 9, 7.40, 0.90, 3.20, NULL, 81, 202, 3761, 1.00, 26.1, 689, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2574, '2025-11-18 09:04:14', 1, 9, 7.40, 3.00, 2.60, NULL, 108, 314, 3262, 0.20, 29.5, 794, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2575, '2025-11-17 11:41:14', 1, 9, 7.60, 2.70, 0.90, NULL, 111, 344, 3587, 1.00, 26.6, 782, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2576, '2025-11-16 10:11:14', 1, 9, 7.10, 2.20, 3.30, NULL, 88, 309, 3186, 0.60, 27.7, 708, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2577, '2025-11-15 11:18:14', 1, 9, 7.50, 1.80, 2.60, NULL, 103, 349, 3757, 0.80, 26.5, 691, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2578, '2025-11-14 08:35:14', 1, 9, 7.40, 2.70, 0.70, NULL, 95, 307, 3569, 1.00, 26.0, 749, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2579, '2025-11-13 09:07:14', 1, 9, 7.80, 1.60, 1.10, NULL, 102, 260, 3558, 0.70, 26.9, 745, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2580, '2025-11-12 09:41:14', 1, 9, 7.80, 1.90, 3.20, NULL, 103, 274, 3203, 0.40, 26.6, 744, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2581, '2025-11-11 08:53:14', 1, 9, 7.60, 1.70, 1.10, NULL, 108, 310, 3947, 0.50, 28.0, 706, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2582, '2025-11-10 10:37:14', 1, 9, 7.40, 2.70, 0.80, NULL, 112, 311, 3295, 0.90, 28.3, 737, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2583, '2025-11-09 10:27:14', 1, 9, 7.60, 3.00, 2.10, NULL, 104, 310, 3432, 0.50, 29.6, 716, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2584, '2025-11-08 11:39:14', 1, 9, 7.30, 1.40, 3.30, NULL, 101, 379, 3319, 0.00, 29.5, 792, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2585, '2025-11-07 11:00:14', 1, 9, 7.00, 1.90, 1.40, NULL, 115, 284, 3503, 0.50, 28.2, 690, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2586, '2025-11-06 11:56:14', 1, 9, 7.60, 1.00, 3.00, NULL, 93, 400, 3543, 0.40, 28.4, 652, 'Routine monthly check', '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2587, '2025-11-05 09:07:14', 1, 9, 7.60, 0.70, 2.00, NULL, 103, 251, 3241, 0.20, 27.9, 784, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2588, '2025-11-04 08:15:14', 1, 9, 7.80, 1.90, 2.20, NULL, 112, 244, 3241, 0.20, 29.1, 714, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2589, '2025-11-03 11:23:14', 1, 9, 7.10, 0.60, 2.20, NULL, 96, 287, 3370, 0.90, 27.9, 676, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2590, '2025-11-02 08:38:14', 1, 9, 7.30, 2.20, 2.60, NULL, 93, 309, 3258, 0.70, 29.5, 684, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2591, '2025-11-01 09:36:14', 1, 9, 7.50, 0.60, 2.00, NULL, 110, 300, 3631, 0.80, 28.6, 686, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2592, '2025-10-31 09:56:14', 1, 9, 7.40, 1.00, 1.00, NULL, 113, 313, 3743, 0.10, 28.6, 703, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2593, '2025-10-30 09:31:14', 1, 9, 7.50, 2.70, 3.20, NULL, 115, 381, 3639, 0.60, 27.7, 683, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2594, '2025-10-29 11:18:14', 1, 9, 7.30, 1.80, 2.80, NULL, 82, 354, 3070, 0.50, 29.0, 783, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2595, '2025-10-28 11:45:14', 1, 9, 7.60, 3.00, 1.00, NULL, 96, 314, 3792, 0.10, 26.3, 677, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2596, '2025-10-27 09:14:14', 1, 9, 7.50, 1.70, 2.20, NULL, 98, 272, 3206, 0.50, 26.4, 743, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2597, '2025-10-26 09:52:14', 1, 9, 7.80, 2.30, 3.00, NULL, 113, 331, 3562, 0.90, 28.1, 758, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2598, '2025-10-25 09:38:14', 1, 9, 7.50, 2.10, 2.30, NULL, 113, 229, 3928, 0.10, 28.4, 758, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2599, '2025-10-24 10:03:14', 1, 9, 7.10, 1.90, 2.30, NULL, 120, 341, 3732, 0.70, 29.5, 735, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2600, '2025-10-23 11:22:14', 1, 9, 7.60, 2.00, 2.60, NULL, 96, 340, 3262, 0.60, 28.9, 660, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2601, '2025-10-22 10:12:14', 1, 9, 7.40, 1.00, 1.90, NULL, 103, 341, 3602, 0.90, 28.4, 769, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2602, '2025-10-21 09:35:14', 1, 9, 7.10, 1.00, 0.70, NULL, 82, 234, 3933, 0.80, 26.5, 722, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2603, '2025-10-20 11:35:14', 1, 9, 7.20, 3.00, 2.00, NULL, 118, 319, 3338, 0.70, 27.4, 759, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2604, '2025-10-19 09:26:14', 1, 9, 7.10, 2.20, 3.10, NULL, 98, 363, 3185, 1.00, 27.3, 711, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2605, '2025-10-18 11:24:14', 1, 9, 7.70, 0.60, 2.20, NULL, 109, 255, 3860, 1.00, 27.2, 674, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2606, '2025-10-17 08:49:14', 1, 9, 7.00, 2.10, 0.50, NULL, 81, 295, 3819, 0.90, 27.9, 791, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2607, '2025-10-16 11:44:14', 1, 9, 7.80, 2.30, 1.40, NULL, 86, 400, 3098, 0.30, 27.6, 778, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2608, '2025-10-15 10:02:14', 1, 9, 7.30, 2.10, 2.60, NULL, 85, 234, 3344, 0.70, 26.7, 673, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2609, '2025-10-14 10:11:14', 1, 9, 7.50, 1.80, 1.50, NULL, 112, 337, 3400, 0.50, 28.4, 714, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2610, '2025-10-13 09:25:14', 1, 9, 7.40, 2.10, 1.40, NULL, 95, 262, 3947, 0.80, 27.1, 722, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2611, '2025-10-12 10:28:14', 1, 9, 7.40, 2.90, 1.10, NULL, 93, 353, 3594, 0.30, 26.3, 788, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2612, '2025-10-11 10:51:14', 1, 9, 7.60, 0.80, 3.30, NULL, 105, 213, 3365, 0.60, 28.3, 708, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2613, '2025-10-10 10:03:14', 1, 9, 7.50, 2.50, 2.10, NULL, 117, 388, 3783, 0.20, 26.4, 721, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2614, '2025-10-09 08:38:14', 1, 9, 7.60, 2.50, 0.90, NULL, 116, 382, 3398, 1.00, 27.3, 661, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2615, '2025-10-08 09:31:14', 1, 9, 7.60, 1.00, 2.40, NULL, 85, 305, 3455, 0.90, 27.3, 747, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2616, '2025-10-07 08:32:14', 1, 9, 7.50, 1.30, 3.10, NULL, 107, 328, 3888, 0.00, 28.3, 796, 'Routine monthly check', '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2617, '2025-10-06 09:18:14', 1, 9, 7.50, 1.80, 0.50, NULL, 90, 343, 3594, 0.10, 29.9, 711, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2618, '2025-10-05 10:18:14', 1, 9, 7.40, 1.90, 1.10, NULL, 102, 283, 3073, 0.70, 26.3, 723, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2619, '2025-10-04 08:52:14', 1, 9, 7.00, 2.20, 1.20, NULL, 113, 296, 3183, 0.30, 29.0, 738, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2620, '2025-10-03 11:21:14', 1, 9, 7.40, 1.00, 0.80, NULL, 112, 332, 3131, 0.00, 29.9, 753, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2621, '2025-10-02 10:22:14', 1, 9, 7.00, 2.00, 0.90, NULL, 115, 323, 3851, 0.00, 28.6, 712, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2622, '2025-10-01 10:23:14', 1, 9, 7.20, 1.40, 1.30, NULL, 84, 333, 3747, 0.90, 26.0, 778, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2623, '2025-09-30 09:41:14', 1, 9, 7.40, 1.60, 0.60, NULL, 102, 325, 3650, 0.20, 29.1, 732, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2624, '2025-09-29 10:06:14', 1, 9, 7.30, 1.40, 0.80, NULL, 90, 260, 3783, 0.00, 28.3, 721, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2625, '2025-09-28 11:45:14', 1, 9, 7.20, 1.50, 0.70, NULL, 83, 349, 3052, 0.20, 28.5, 753, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2626, '2025-09-27 11:46:14', 1, 9, 7.70, 1.60, 0.60, NULL, 110, 361, 3963, 0.00, 28.0, 766, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2627, '2025-09-26 08:34:14', 1, 9, 7.00, 2.20, 2.20, NULL, 114, 264, 3445, 1.00, 28.7, 800, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2628, '2025-09-25 09:38:14', 1, 9, 7.20, 1.10, 0.50, NULL, 87, 222, 3114, 0.20, 29.8, 723, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2629, '2025-09-24 08:03:14', 1, 9, 7.40, 1.80, 2.00, NULL, 120, 325, 3704, 0.90, 26.3, 766, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2630, '2025-09-23 08:26:14', 1, 9, 7.40, 0.50, 0.90, NULL, 116, 267, 3118, 0.10, 26.7, 661, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2631, '2025-09-22 08:08:14', 1, 9, 7.10, 1.50, 1.70, NULL, 114, 205, 3996, 0.00, 28.5, 778, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2632, '2025-09-21 09:25:14', 1, 9, 7.50, 0.60, 1.70, NULL, 81, 334, 3156, 0.80, 28.2, 714, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2633, '2025-09-20 08:07:14', 1, 9, 7.00, 0.80, 1.50, NULL, 118, 227, 3190, 0.20, 27.1, 695, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2634, '2025-09-19 10:26:14', 1, 9, 7.60, 0.90, 1.70, NULL, 110, 288, 3787, 0.90, 26.3, 670, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2635, '2025-09-18 08:52:14', 1, 9, 7.60, 1.00, 1.30, NULL, 115, 273, 3390, 0.10, 30.0, 762, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2636, '2025-09-17 09:39:14', 1, 9, 7.00, 1.50, 2.60, NULL, 106, 280, 3938, 0.60, 26.4, 655, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2637, '2025-09-16 09:19:14', 1, 9, 7.40, 1.00, 1.00, NULL, 94, 372, 3715, 0.60, 30.0, 722, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2638, '2025-09-15 08:07:14', 1, 9, 7.10, 2.00, 1.10, NULL, 81, 201, 3902, 1.00, 28.9, 755, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2639, '2025-09-14 09:09:14', 1, 9, 7.50, 2.10, 2.90, NULL, 118, 287, 3042, 0.40, 28.2, 652, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2640, '2025-09-13 10:54:14', 1, 9, 7.20, 2.60, 0.60, NULL, 113, 363, 3001, 0.80, 29.2, 724, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2641, '2025-09-12 11:14:14', 1, 9, 7.00, 2.90, 2.20, NULL, 85, 360, 3260, 0.40, 28.6, 768, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2642, '2025-09-11 08:54:14', 1, 9, 7.80, 3.00, 3.50, NULL, 105, 361, 3574, 0.60, 29.9, 730, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2643, '2025-09-10 10:40:14', 1, 9, 7.10, 2.50, 3.30, NULL, 96, 306, 3293, 0.50, 26.3, 662, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2644, '2025-09-09 08:38:14', 1, 9, 7.60, 1.30, 3.00, NULL, 84, 230, 3567, 1.00, 28.3, 705, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2645, '2025-09-08 08:11:14', 1, 9, 7.80, 1.70, 0.90, NULL, 120, 279, 3958, 0.10, 28.0, 651, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2646, '2025-09-07 09:29:14', 1, 9, 7.50, 2.20, 0.50, NULL, 116, 349, 3896, 0.80, 26.4, 774, 'Routine monthly check', '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2647, '2025-09-06 10:49:14', 1, 9, 7.10, 2.80, 2.30, NULL, 113, 238, 3424, 0.00, 30.0, 778, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2648, '2025-09-05 10:16:14', 1, 9, 7.50, 3.00, 1.10, NULL, 104, 393, 3261, 0.00, 27.9, 698, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2649, '2025-09-04 09:11:14', 1, 9, 7.40, 1.30, 1.10, NULL, 92, 280, 3182, 0.70, 26.5, 766, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2650, '2025-09-03 11:03:14', 1, 9, 7.80, 2.00, 1.00, NULL, 84, 253, 3778, 1.00, 28.3, 786, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2651, '2025-09-02 08:08:14', 1, 9, 7.20, 1.40, 2.50, NULL, 111, 381, 3030, 0.90, 26.1, 698, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2652, '2025-09-01 08:58:14', 1, 9, 7.60, 1.60, 2.80, NULL, 114, 368, 3400, 0.00, 26.1, 691, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2653, '2025-08-31 09:16:14', 1, 9, 7.20, 2.40, 1.60, NULL, 95, 327, 3471, 1.00, 28.8, 738, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2654, '2025-08-30 10:32:14', 1, 9, 7.60, 1.00, 2.50, NULL, 112, 210, 3993, 0.30, 26.4, 679, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2655, '2025-08-29 08:11:14', 1, 9, 7.50, 2.50, 2.10, NULL, 87, 352, 3216, 0.80, 27.0, 719, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2656, '2025-08-28 09:03:14', 1, 9, 7.50, 2.50, 2.20, NULL, 107, 280, 3917, 0.60, 29.6, 720, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2657, '2025-08-27 10:56:14', 1, 9, 7.00, 1.50, 0.90, NULL, 106, 376, 3562, 0.60, 29.5, 722, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2658, '2025-08-26 10:11:14', 1, 9, 7.50, 1.10, 1.50, NULL, 104, 320, 3303, 0.40, 28.4, 758, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2659, '2025-08-25 10:16:14', 1, 9, 7.50, 2.20, 3.30, NULL, 90, 377, 3116, 0.60, 29.3, 678, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2660, '2025-08-24 10:36:14', 1, 9, 7.00, 0.50, 3.30, NULL, 93, 288, 3768, 0.50, 26.5, 655, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2661, '2025-08-23 09:09:14', 1, 9, 7.50, 2.70, 3.20, NULL, 100, 282, 3769, 0.70, 27.7, 661, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2662, '2025-08-22 10:01:14', 1, 9, 7.10, 2.90, 1.90, NULL, 94, 209, 3575, 0.70, 29.6, 673, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2663, '2025-08-21 10:13:14', 1, 9, 7.50, 1.70, 3.50, NULL, 86, 222, 3065, 0.70, 28.9, 684, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2664, '2025-08-20 10:27:14', 1, 9, 7.40, 3.00, 1.00, NULL, 108, 265, 3852, 0.90, 29.9, 785, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2665, '2025-08-19 09:23:14', 1, 9, 7.30, 0.60, 1.60, NULL, 98, 217, 3444, 0.90, 27.1, 800, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2666, '2025-08-18 08:46:14', 1, 9, 7.60, 2.10, 2.60, NULL, 104, 294, 3142, 1.00, 28.5, 735, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2667, '2025-08-17 10:37:14', 1, 9, 7.40, 2.00, 2.80, NULL, 86, 356, 3302, 0.20, 26.7, 664, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2668, '2025-08-16 08:32:14', 1, 9, 7.30, 0.90, 1.50, NULL, 100, 223, 3821, 0.70, 26.4, 713, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2669, '2025-08-15 08:52:14', 1, 9, 7.60, 2.90, 2.80, NULL, 84, 258, 3445, 0.80, 28.6, 781, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2670, '2025-08-14 08:39:14', 1, 9, 7.30, 1.40, 0.60, NULL, 89, 237, 3352, 0.40, 26.4, 650, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2671, '2025-08-13 09:31:14', 1, 9, 7.30, 0.70, 0.50, NULL, 101, 382, 3436, 0.30, 28.0, 690, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2672, '2025-08-12 09:41:14', 1, 9, 7.60, 2.00, 2.90, NULL, 81, 326, 3068, 0.00, 26.5, 744, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2673, '2025-08-11 09:27:14', 1, 9, 7.00, 2.80, 0.90, NULL, 95, 237, 3034, 0.30, 29.4, 658, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2674, '2025-08-10 08:40:14', 1, 9, 7.40, 0.70, 0.60, NULL, 95, 224, 3367, 0.10, 27.8, 681, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2675, '2025-08-09 11:52:14', 1, 9, 7.50, 2.00, 3.50, NULL, 85, 258, 3052, 0.10, 30.0, 735, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2676, '2025-08-08 11:57:14', 1, 9, 7.50, 2.80, 3.30, NULL, 103, 289, 3636, 0.70, 28.0, 653, 'Routine monthly check', '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2677, '2025-08-07 11:46:14', 1, 9, 7.00, 0.80, 0.90, NULL, 120, 295, 3364, 0.70, 28.2, 751, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2678, '2025-08-06 10:25:14', 1, 9, 7.80, 2.90, 0.60, NULL, 118, 323, 3626, 0.00, 28.6, 706, NULL, '2025-12-06 12:13:14', '2025-12-06 12:13:14');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2679, '2025-08-05 11:57:14', 1, 9, 7.10, 0.70, 1.30, NULL, 91, 261, 3523, 0.60, 26.5, 718, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2680, '2025-08-04 10:20:15', 1, 9, 7.10, 1.70, 0.60, NULL, 81, 215, 3113, 0.20, 26.0, 717, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2681, '2025-08-03 10:33:15', 1, 9, 7.30, 1.50, 3.50, NULL, 115, 336, 3418, 0.60, 27.2, 705, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2682, '2025-08-02 10:35:15', 1, 9, 7.30, 1.40, 2.60, NULL, 85, 237, 3053, 0.00, 28.1, 739, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2683, '2025-08-01 10:12:15', 1, 9, 7.20, 1.90, 1.70, NULL, 84, 262, 3488, 0.50, 27.0, 655, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2684, '2025-07-31 08:44:15', 1, 9, 7.20, 2.40, 0.80, NULL, 100, 292, 3858, 0.90, 27.7, 702, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2685, '2025-07-30 08:56:15', 1, 9, 7.50, 0.80, 3.50, NULL, 87, 324, 3163, 0.60, 29.7, 701, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2686, '2025-07-29 11:27:15', 1, 9, 7.10, 0.90, 3.40, NULL, 94, 320, 3560, 1.00, 26.1, 733, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2687, '2025-07-28 09:01:15', 1, 9, 7.60, 2.80, 3.10, NULL, 114, 221, 3763, 0.50, 26.3, 652, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2688, '2025-07-27 09:54:15', 1, 9, 7.50, 0.50, 2.40, NULL, 86, 342, 3339, 1.00, 29.4, 693, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2689, '2025-07-26 08:45:15', 1, 9, 7.00, 0.70, 2.20, NULL, 91, 378, 3170, 0.70, 26.4, 718, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2690, '2025-07-25 11:48:15', 1, 9, 7.60, 0.80, 2.80, NULL, 112, 394, 3497, 1.00, 29.0, 729, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2691, '2025-07-24 08:10:15', 1, 9, 7.20, 2.00, 1.70, NULL, 101, 382, 3203, 0.40, 27.3, 749, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2692, '2025-07-23 10:30:15', 1, 9, 7.30, 1.30, 0.80, NULL, 106, 339, 3058, 0.80, 28.4, 703, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2693, '2025-07-22 10:04:15', 1, 9, 7.00, 0.90, 2.00, NULL, 116, 301, 3193, 0.60, 26.2, 800, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2694, '2025-07-21 08:27:15', 1, 9, 7.40, 1.60, 1.10, NULL, 104, 281, 3750, 0.00, 26.0, 772, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2695, '2025-07-20 09:01:15', 1, 9, 7.50, 2.40, 1.30, NULL, 105, 263, 3430, 0.90, 29.5, 700, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2696, '2025-07-19 08:34:15', 1, 9, 7.50, 2.50, 1.40, NULL, 116, 215, 3112, 0.70, 29.8, 713, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2697, '2025-07-18 09:41:15', 1, 9, 7.40, 1.70, 3.40, NULL, 113, 371, 3687, 0.10, 29.3, 767, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2698, '2025-07-17 09:09:15', 1, 9, 7.50, 2.50, 2.90, NULL, 97, 376, 3711, 0.80, 26.2, 766, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2699, '2025-07-16 10:49:15', 1, 9, 7.50, 1.70, 1.20, NULL, 100, 266, 3897, 0.70, 27.9, 675, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2700, '2025-07-15 10:10:15', 1, 9, 7.10, 2.60, 3.00, NULL, 120, 362, 3430, 0.00, 30.0, 778, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2701, '2025-07-14 11:51:15', 1, 9, 7.40, 2.80, 1.00, NULL, 109, 319, 3147, 0.30, 29.5, 741, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2702, '2025-07-13 10:11:15', 1, 9, 7.30, 1.80, 2.70, NULL, 94, 385, 3718, 0.20, 29.0, 711, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2703, '2025-07-12 11:36:15', 1, 9, 7.70, 1.60, 2.60, NULL, 95, 294, 3973, 1.00, 27.3, 699, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2704, '2025-07-11 08:38:15', 1, 9, 7.60, 1.70, 1.70, NULL, 101, 331, 3526, 0.80, 27.1, 748, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2705, '2025-07-10 11:53:15', 1, 9, 7.40, 0.80, 2.10, NULL, 82, 259, 3175, 0.90, 27.6, 653, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2706, '2025-07-09 11:33:15', 1, 9, 7.50, 0.70, 2.70, NULL, 109, 355, 3456, 0.60, 27.8, 745, 'Routine monthly check', '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2707, '2025-07-08 09:08:15', 1, 9, 7.20, 1.20, 1.60, NULL, 101, 305, 3495, 0.00, 29.0, 674, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2708, '2025-07-07 09:58:15', 1, 9, 7.80, 0.50, 2.30, NULL, 88, 318, 3695, 0.00, 28.4, 721, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2709, '2025-07-06 09:00:15', 1, 9, 7.40, 1.10, 1.10, NULL, 100, 306, 3361, 0.30, 27.0, 723, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2710, '2025-07-05 11:45:15', 1, 9, 7.00, 1.30, 0.90, NULL, 117, 268, 3330, 0.10, 29.3, 741, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2711, '2025-07-04 08:13:15', 1, 9, 7.50, 2.60, 1.50, NULL, 94, 238, 3372, 0.10, 28.3, 789, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2712, '2025-07-03 08:44:15', 1, 9, 7.60, 1.10, 1.10, NULL, 101, 397, 3052, 1.00, 30.0, 720, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2713, '2025-07-02 08:13:15', 1, 9, 7.70, 1.90, 3.40, NULL, 113, 379, 3011, 0.40, 29.0, 662, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2714, '2025-07-01 10:20:15', 1, 9, 7.40, 2.80, 2.10, NULL, 111, 337, 3964, 0.60, 28.3, 663, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2715, '2025-06-30 08:10:15', 1, 9, 7.20, 1.50, 1.40, NULL, 115, 224, 3189, 0.00, 27.6, 771, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2716, '2025-06-29 09:14:15', 1, 9, 7.00, 2.80, 0.60, NULL, 100, 380, 3970, 0.20, 28.8, 732, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2717, '2025-06-28 10:25:15', 1, 9, 7.40, 0.70, 2.40, NULL, 97, 235, 3618, 0.20, 30.0, 658, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2718, '2025-06-27 11:17:15', 1, 9, 7.80, 2.60, 1.60, NULL, 80, 214, 3954, 0.20, 27.1, 775, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2719, '2025-06-26 10:25:15', 1, 9, 7.70, 2.60, 1.70, NULL, 104, 256, 3448, 0.30, 30.0, 650, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2720, '2025-06-25 08:12:15', 1, 9, 7.10, 1.50, 3.20, NULL, 94, 234, 3133, 0.20, 28.0, 674, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2721, '2025-06-24 09:35:15', 1, 9, 7.40, 2.00, 1.00, NULL, 89, 317, 3885, 1.00, 28.0, 761, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2722, '2025-06-23 11:59:15', 1, 9, 7.20, 2.90, 3.00, NULL, 102, 378, 3726, 0.80, 27.4, 695, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2723, '2025-06-22 10:35:15', 1, 9, 7.00, 1.90, 2.90, NULL, 116, 386, 3250, 0.10, 29.9, 789, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2724, '2025-06-21 08:58:15', 1, 9, 7.60, 1.50, 2.70, NULL, 91, 232, 3195, 0.10, 27.8, 768, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2725, '2025-06-20 09:13:15', 1, 9, 7.20, 1.20, 2.30, NULL, 119, 243, 3916, 1.00, 26.7, 757, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2726, '2025-06-19 11:17:15', 1, 9, 7.50, 1.20, 1.20, NULL, 106, 354, 3334, 0.10, 26.0, 780, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2727, '2025-06-18 11:50:15', 1, 9, 7.40, 2.50, 1.40, NULL, 109, 387, 3825, 0.90, 27.2, 655, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2728, '2025-06-17 09:14:15', 1, 9, 7.80, 1.50, 2.80, NULL, 119, 393, 3982, 0.50, 26.5, 769, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2729, '2025-06-16 11:52:15', 1, 9, 7.10, 0.50, 0.60, NULL, 87, 358, 3240, 0.10, 29.1, 718, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2730, '2025-06-15 10:45:15', 1, 9, 7.00, 1.90, 0.70, NULL, 120, 256, 3494, 0.70, 28.3, 718, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2731, '2025-06-14 09:16:15', 1, 9, 7.40, 1.30, 2.10, NULL, 83, 320, 3668, 0.00, 28.6, 680, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2732, '2025-06-13 10:00:15', 1, 9, 7.40, 3.00, 1.10, NULL, 95, 340, 3754, 1.00, 29.8, 689, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2733, '2025-06-12 10:23:15', 1, 9, 7.40, 2.30, 0.80, NULL, 100, 345, 3732, 0.00, 26.4, 766, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2734, '2025-06-11 11:30:15', 1, 9, 7.70, 1.70, 3.20, NULL, 98, 287, 3379, 0.10, 27.7, 765, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2735, '2025-06-10 11:45:15', 1, 9, 7.20, 2.10, 1.50, NULL, 95, 357, 3651, 1.00, 29.9, 698, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2736, '2025-06-09 11:12:15', 1, 9, 7.10, 2.90, 0.60, NULL, 100, 342, 3227, 1.00, 26.7, 695, 'Routine monthly check', '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2737, '2025-06-08 08:44:15', 1, 9, 7.20, 2.90, 1.50, NULL, 86, 280, 3107, 0.10, 26.9, 701, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2738, '2025-06-07 10:56:15', 1, 9, 7.10, 2.50, 2.60, NULL, 104, 341, 3522, 0.80, 29.6, 738, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2739, '2025-06-06 11:41:15', 1, 9, 7.60, 3.00, 2.70, NULL, 90, 371, 3352, 0.90, 30.0, 715, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2740, '2025-06-05 08:37:15', 1, 9, 7.10, 2.30, 2.40, NULL, 102, 223, 3015, 0.80, 28.5, 699, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2741, '2025-06-04 11:10:15', 1, 9, 7.00, 0.80, 1.80, NULL, 115, 249, 3825, 0.10, 27.7, 700, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2742, '2025-06-03 08:49:15', 1, 9, 7.40, 1.50, 0.60, NULL, 108, 325, 3942, 0.60, 27.6, 658, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2743, '2025-06-02 10:58:15', 1, 9, 7.80, 1.50, 2.60, NULL, 97, 258, 3353, 0.70, 30.0, 687, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2744, '2025-06-01 08:15:15', 1, 9, 7.10, 2.30, 2.50, NULL, 98, 383, 3168, 0.30, 29.4, 786, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2745, '2025-05-31 09:38:15', 1, 9, 7.20, 1.60, 1.20, NULL, 93, 321, 3332, 0.40, 27.7, 745, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2746, '2025-05-30 08:47:15', 1, 9, 7.70, 2.60, 1.00, NULL, 97, 296, 3515, 0.20, 28.7, 762, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2747, '2025-05-29 09:02:15', 1, 9, 7.30, 2.30, 2.90, NULL, 120, 331, 3486, 0.10, 28.1, 775, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2748, '2025-05-28 09:45:15', 1, 9, 7.30, 2.00, 0.60, NULL, 118, 220, 3255, 0.70, 27.9, 778, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2749, '2025-05-27 11:20:15', 1, 9, 7.80, 2.70, 0.60, NULL, 85, 387, 3562, 0.60, 26.6, 708, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2750, '2025-05-26 08:26:15', 1, 9, 7.40, 2.50, 1.60, NULL, 100, 273, 3190, 0.70, 29.1, 711, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2751, '2025-05-25 08:51:15', 1, 9, 7.10, 1.20, 2.60, NULL, 93, 299, 3411, 0.70, 27.2, 736, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2752, '2025-05-24 09:42:15', 1, 9, 7.20, 3.00, 0.80, NULL, 82, 371, 3848, 0.30, 28.9, 712, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2753, '2025-05-23 10:46:15', 1, 9, 7.10, 1.80, 2.90, NULL, 88, 233, 3257, 0.40, 26.2, 772, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2754, '2025-05-22 09:06:15', 1, 9, 7.20, 0.60, 1.40, NULL, 112, 239, 3721, 0.00, 28.9, 730, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2755, '2025-05-21 10:23:15', 1, 9, 7.00, 3.00, 3.50, NULL, 120, 272, 3452, 0.10, 26.0, 687, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2756, '2025-05-20 09:34:15', 1, 9, 7.20, 1.70, 2.50, NULL, 113, 276, 3826, 0.10, 28.9, 769, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2757, '2025-05-19 10:08:15', 1, 9, 7.50, 2.20, 0.80, NULL, 87, 235, 3449, 0.90, 27.6, 696, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2758, '2025-05-18 10:50:15', 1, 9, 7.40, 1.10, 2.40, NULL, 89, 277, 3852, 1.00, 29.7, 683, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2759, '2025-05-17 10:56:15', 1, 9, 7.20, 1.20, 0.50, NULL, 113, 202, 3968, 0.20, 26.2, 705, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2760, '2025-05-16 11:44:15', 1, 9, 7.10, 0.80, 0.70, NULL, 117, 382, 3045, 0.50, 27.8, 763, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2761, '2025-05-15 10:36:15', 1, 9, 7.30, 0.50, 1.10, NULL, 106, 287, 3694, 0.40, 27.2, 670, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2762, '2025-05-14 08:27:15', 1, 9, 7.50, 2.10, 3.00, NULL, 88, 325, 3450, 0.80, 27.6, 772, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2763, '2025-05-13 10:11:15', 1, 9, 7.10, 2.50, 2.60, NULL, 84, 269, 3136, 0.30, 30.0, 796, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2764, '2025-05-12 08:07:15', 1, 9, 7.00, 2.20, 2.10, NULL, 108, 242, 3684, 0.20, 26.9, 703, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2765, '2025-05-11 09:09:15', 1, 9, 7.60, 2.10, 1.80, NULL, 119, 213, 3767, 0.70, 28.8, 650, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2766, '2025-05-10 11:33:15', 1, 9, 7.40, 2.50, 3.20, NULL, 84, 382, 3702, 0.00, 26.0, 715, 'Routine monthly check', '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2767, '2025-05-09 11:15:15', 1, 9, 7.80, 1.90, 0.90, NULL, 85, 206, 3771, 0.50, 28.0, 724, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2768, '2025-05-08 09:37:15', 1, 9, 7.10, 1.50, 2.60, NULL, 84, 348, 3219, 0.70, 27.2, 667, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2769, '2025-05-07 09:30:15', 1, 9, 7.20, 0.70, 3.00, NULL, 105, 268, 3718, 0.40, 27.0, 730, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2770, '2025-05-06 11:44:15', 1, 9, 7.50, 2.20, 3.40, NULL, 106, 278, 3421, 0.90, 26.5, 670, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2771, '2025-05-05 11:55:15', 1, 9, 7.10, 1.60, 3.50, NULL, 98, 343, 3563, 0.80, 29.5, 730, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2772, '2025-05-04 10:23:15', 1, 9, 7.30, 1.00, 2.40, NULL, 94, 299, 3394, 0.00, 28.5, 672, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2773, '2025-05-03 11:58:15', 1, 9, 7.70, 1.50, 2.70, NULL, 86, 268, 3803, 1.00, 29.8, 734, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2774, '2025-05-02 08:59:15', 1, 9, 7.20, 2.40, 2.10, NULL, 106, 353, 3906, 0.70, 28.4, 790, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2775, '2025-05-01 11:50:15', 1, 9, 7.80, 2.30, 1.40, NULL, 104, 322, 3909, 0.80, 29.7, 671, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2776, '2025-04-30 08:08:15', 1, 9, 7.50, 1.60, 3.40, NULL, 118, 247, 3441, 0.50, 26.1, 663, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2777, '2025-04-29 08:29:15', 1, 9, 7.20, 2.40, 0.80, NULL, 97, 391, 3737, 0.30, 27.1, 678, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2778, '2025-04-28 08:53:15', 1, 9, 7.30, 3.00, 2.90, NULL, 103, 228, 3515, 0.80, 26.8, 685, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2779, '2025-04-27 10:13:15', 1, 9, 7.70, 1.50, 2.30, NULL, 105, 294, 3366, 1.00, 29.5, 724, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2780, '2025-04-26 11:51:15', 1, 9, 7.50, 1.40, 1.60, NULL, 83, 305, 3861, 1.00, 26.5, 665, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2781, '2025-04-25 11:03:15', 1, 9, 7.20, 0.70, 2.20, NULL, 88, 359, 3502, 1.00, 27.7, 735, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2782, '2025-04-24 11:41:15', 1, 9, 7.00, 2.50, 2.00, NULL, 118, 208, 3148, 0.40, 28.1, 759, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2783, '2025-04-23 10:57:15', 1, 9, 7.50, 0.70, 1.80, NULL, 96, 204, 3190, 0.30, 27.9, 710, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2784, '2025-04-22 10:11:15', 1, 9, 7.30, 2.50, 1.00, NULL, 115, 231, 3920, 0.20, 27.9, 741, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2785, '2025-04-21 09:08:15', 1, 9, 7.50, 1.90, 2.10, NULL, 91, 202, 3478, 0.30, 29.1, 741, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2786, '2025-04-20 08:57:15', 1, 9, 7.50, 0.70, 3.50, NULL, 97, 269, 3402, 0.40, 27.2, 698, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2787, '2025-04-19 09:01:15', 1, 9, 7.60, 0.60, 3.20, NULL, 89, 397, 3103, 0.20, 28.7, 709, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2788, '2025-04-18 10:56:15', 1, 9, 7.10, 1.50, 2.00, NULL, 112, 250, 3800, 0.00, 27.2, 682, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2789, '2025-04-17 08:50:15', 1, 9, 7.40, 2.10, 0.90, NULL, 86, 366, 3372, 1.00, 28.7, 686, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2790, '2025-04-16 11:20:15', 1, 9, 7.30, 2.70, 0.70, NULL, 112, 379, 3263, 0.20, 26.8, 780, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2791, '2025-04-15 10:07:15', 1, 9, 7.60, 2.70, 0.90, NULL, 115, 256, 3598, 0.00, 26.7, 785, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2792, '2025-04-14 08:30:15', 1, 9, 7.50, 1.30, 3.00, NULL, 97, 248, 3428, 0.50, 28.5, 669, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2793, '2025-04-13 10:04:15', 1, 9, 7.30, 1.10, 2.80, NULL, 84, 210, 3170, 0.10, 28.1, 769, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2794, '2025-04-12 10:37:15', 1, 9, 7.60, 1.90, 2.50, NULL, 106, 399, 3926, 0.50, 26.4, 748, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2795, '2025-04-11 10:17:15', 1, 9, 7.80, 0.70, 3.10, NULL, 111, 256, 3693, 0.70, 28.7, 696, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2796, '2025-04-10 08:01:15', 1, 9, 7.60, 0.90, 1.60, NULL, 115, 350, 3696, 0.80, 29.3, 783, 'Routine monthly check', '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2797, '2025-04-09 08:18:15', 1, 9, 7.40, 1.10, 0.70, NULL, 101, 349, 3500, 0.20, 26.7, 789, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2798, '2025-04-08 09:39:15', 1, 9, 7.60, 1.00, 1.10, NULL, 87, 352, 3225, 0.60, 26.9, 700, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2799, '2025-04-07 08:18:15', 1, 9, 7.10, 1.40, 1.70, NULL, 82, 324, 3748, 1.00, 28.6, 760, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2800, '2025-04-06 08:43:15', 1, 9, 7.80, 0.80, 3.50, NULL, 97, 349, 3214, 0.30, 29.8, 746, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2801, '2025-04-05 11:36:15', 1, 9, 7.70, 0.80, 2.80, NULL, 80, 278, 3644, 0.50, 28.0, 670, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2802, '2025-04-04 10:45:15', 1, 9, 7.80, 1.50, 1.10, NULL, 91, 212, 3109, 0.00, 29.6, 761, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2803, '2025-04-03 11:14:15', 1, 9, 7.30, 2.10, 1.00, NULL, 94, 297, 3603, 0.60, 29.2, 778, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2804, '2025-04-02 08:58:15', 1, 9, 7.10, 1.60, 1.20, NULL, 86, 351, 3092, 0.30, 27.4, 798, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2805, '2025-04-01 08:40:15', 1, 9, 7.50, 1.50, 2.20, NULL, 116, 226, 3189, 0.00, 26.9, 792, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2806, '2025-03-31 09:34:15', 1, 9, 7.50, 1.30, 3.40, NULL, 81, 271, 3968, 0.10, 29.9, 700, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2807, '2025-03-30 10:57:15', 1, 9, 7.00, 0.90, 0.80, NULL, 99, 359, 3334, 0.30, 26.7, 741, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2808, '2025-03-29 10:12:15', 1, 9, 7.50, 1.20, 2.60, NULL, 104, 250, 3626, 1.00, 29.7, 682, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2809, '2025-03-28 10:14:15', 1, 9, 7.80, 2.70, 0.50, NULL, 85, 323, 3516, 0.80, 26.9, 687, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2810, '2025-03-27 09:26:15', 1, 9, 7.50, 1.90, 3.50, NULL, 111, 338, 3230, 0.20, 27.4, 723, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2811, '2025-03-26 09:05:15', 1, 9, 7.00, 2.30, 3.50, NULL, 110, 322, 3851, 0.10, 29.4, 662, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2812, '2025-03-25 10:32:15', 1, 9, 7.70, 2.50, 1.20, NULL, 107, 252, 3984, 0.50, 29.1, 773, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2813, '2025-03-24 10:03:15', 1, 9, 7.50, 1.10, 2.60, NULL, 93, 271, 3343, 0.40, 26.3, 659, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2814, '2025-03-23 11:43:15', 1, 9, 7.10, 0.50, 2.80, NULL, 80, 233, 3648, 0.90, 28.8, 751, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2815, '2025-03-22 10:09:15', 1, 9, 7.80, 3.00, 0.90, NULL, 98, 284, 3582, 0.40, 29.5, 704, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2816, '2025-03-21 08:37:15', 1, 9, 7.80, 2.30, 3.30, NULL, 118, 395, 3904, 0.20, 27.6, 746, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2817, '2025-03-20 09:50:15', 1, 9, 7.40, 0.80, 1.40, NULL, 119, 228, 3530, 0.40, 27.1, 687, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2818, '2025-03-19 08:53:15', 1, 9, 7.70, 1.60, 1.10, NULL, 117, 243, 3557, 0.70, 26.2, 693, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2819, '2025-03-18 08:51:15', 1, 9, 7.70, 2.60, 2.50, NULL, 80, 233, 3350, 0.40, 26.9, 739, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2820, '2025-03-17 09:35:15', 1, 9, 7.40, 2.20, 0.60, NULL, 111, 215, 3608, 0.80, 27.4, 658, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2821, '2025-03-16 08:55:15', 1, 9, 7.50, 1.60, 3.00, NULL, 89, 237, 3997, 0.10, 29.5, 793, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2822, '2025-03-15 09:39:15', 1, 9, 7.80, 1.50, 1.60, NULL, 108, 207, 3496, 0.40, 26.4, 685, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2823, '2025-03-14 10:36:15', 1, 9, 7.00, 0.50, 1.70, NULL, 117, 226, 3914, 0.00, 27.7, 656, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2824, '2025-03-13 08:32:15', 1, 9, 7.40, 1.30, 1.40, NULL, 83, 223, 3349, 1.00, 28.4, 794, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2825, '2025-03-12 09:33:15', 1, 9, 7.10, 0.60, 0.60, NULL, 82, 340, 3657, 0.40, 27.5, 667, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2826, '2025-03-11 11:57:15', 1, 9, 7.50, 2.70, 1.70, NULL, 94, 276, 3590, 0.90, 28.4, 656, 'Routine monthly check', '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2827, '2025-03-10 10:34:15', 1, 9, 7.40, 3.00, 3.40, NULL, 85, 304, 3144, 0.30, 29.1, 758, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2828, '2025-03-09 09:33:15', 1, 9, 7.80, 1.60, 3.20, NULL, 98, 335, 3674, 0.60, 26.4, 706, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2829, '2025-03-08 08:35:15', 1, 9, 7.40, 2.70, 2.50, NULL, 119, 392, 3153, 0.60, 26.5, 782, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2830, '2025-03-07 11:01:15', 1, 9, 7.60, 1.80, 0.50, NULL, 99, 367, 3822, 0.80, 26.3, 790, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2831, '2025-03-06 11:52:15', 1, 9, 7.10, 1.90, 1.10, NULL, 80, 260, 3534, 0.10, 27.7, 761, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2832, '2025-03-05 08:26:15', 1, 9, 7.50, 1.00, 2.30, NULL, 112, 315, 3966, 0.50, 28.5, 662, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2833, '2025-03-04 11:30:15', 1, 9, 7.20, 1.20, 1.60, NULL, 108, 384, 3747, 0.60, 28.8, 709, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2834, '2025-03-03 11:48:15', 1, 9, 7.10, 2.20, 3.40, NULL, 109, 380, 3397, 0.20, 26.2, 675, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2835, '2025-03-02 08:44:15', 1, 9, 7.80, 1.80, 1.40, NULL, 84, 283, 3657, 0.00, 28.7, 780, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2836, '2025-03-01 11:41:15', 1, 9, 7.00, 0.80, 1.70, NULL, 104, 204, 3649, 0.00, 30.0, 676, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2837, '2025-02-28 09:09:15', 1, 9, 7.20, 2.90, 1.40, NULL, 91, 360, 3628, 0.70, 28.2, 793, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2838, '2025-02-27 08:35:15', 1, 9, 7.00, 0.60, 1.90, NULL, 115, 220, 3970, 0.10, 28.0, 732, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2839, '2025-02-26 09:24:15', 1, 9, 7.30, 2.70, 2.80, NULL, 105, 324, 3218, 1.00, 28.5, 686, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2840, '2025-02-25 10:24:15', 1, 9, 7.70, 2.80, 1.60, NULL, 114, 277, 3799, 0.30, 29.7, 711, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2841, '2025-02-24 11:08:15', 1, 9, 7.00, 2.80, 0.50, NULL, 109, 204, 3452, 0.80, 27.0, 659, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2842, '2025-02-23 09:43:15', 1, 9, 7.70, 3.00, 1.30, NULL, 94, 315, 3058, 0.40, 26.6, 692, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2843, '2025-02-22 09:58:15', 1, 9, 7.10, 1.30, 1.00, NULL, 115, 377, 3411, 0.30, 26.2, 778, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2844, '2025-02-21 09:58:15', 1, 9, 7.80, 1.70, 2.40, NULL, 84, 340, 3631, 0.70, 27.6, 722, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2845, '2025-02-20 08:38:15', 1, 9, 7.70, 1.10, 1.20, NULL, 88, 231, 3170, 1.00, 30.0, 658, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2846, '2025-02-19 08:34:15', 1, 9, 7.30, 2.30, 3.20, NULL, 99, 234, 3254, 0.70, 26.4, 712, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2847, '2025-02-18 09:44:15', 1, 9, 7.30, 2.60, 0.60, NULL, 116, 347, 3404, 0.10, 26.5, 764, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2848, '2025-02-17 11:30:15', 1, 9, 7.50, 0.70, 0.90, NULL, 93, 266, 3559, 0.20, 28.0, 788, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2849, '2025-02-16 10:50:15', 1, 9, 7.80, 1.40, 2.30, NULL, 98, 310, 3324, 0.90, 29.9, 692, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2850, '2025-02-15 11:21:15', 1, 9, 7.70, 1.40, 1.10, NULL, 115, 290, 3910, 0.30, 27.4, 779, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2851, '2025-02-14 10:14:15', 1, 9, 7.40, 0.60, 1.90, NULL, 97, 283, 3699, 0.00, 29.6, 665, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2852, '2025-02-13 11:51:15', 1, 9, 7.80, 2.60, 2.80, NULL, 103, 350, 3701, 0.90, 29.0, 705, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2853, '2025-02-12 08:33:15', 1, 9, 7.00, 3.00, 2.20, NULL, 93, 268, 3711, 0.80, 26.3, 760, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2854, '2025-02-11 11:55:15', 1, 9, 7.60, 1.10, 2.50, NULL, 83, 388, 3963, 0.10, 26.0, 753, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2855, '2025-02-10 08:12:15', 1, 9, 7.20, 2.10, 2.60, NULL, 104, 325, 3594, 0.00, 29.6, 767, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2856, '2025-02-09 09:38:15', 1, 9, 7.20, 1.60, 3.40, NULL, 103, 380, 3759, 0.30, 27.9, 771, 'Routine monthly check', '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2857, '2025-02-08 09:13:15', 1, 9, 7.60, 0.80, 2.90, NULL, 120, 204, 3034, 0.40, 27.8, 653, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2858, '2025-02-07 09:54:15', 1, 9, 7.80, 2.70, 3.40, NULL, 103, 341, 3882, 1.00, 27.0, 707, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2859, '2025-02-06 11:56:15', 1, 9, 7.60, 2.20, 2.80, NULL, 99, 321, 3412, 0.30, 27.3, 669, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2860, '2025-02-05 09:41:15', 1, 9, 7.20, 0.60, 2.00, NULL, 116, 225, 3633, 1.00, 26.4, 658, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2861, '2025-02-04 09:18:15', 1, 9, 7.20, 3.00, 2.10, NULL, 120, 371, 3559, 0.10, 27.9, 797, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2862, '2025-02-03 08:20:15', 1, 9, 7.80, 1.60, 1.20, NULL, 91, 338, 3849, 0.90, 26.2, 679, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2863, '2025-02-02 08:59:15', 1, 9, 7.60, 0.70, 1.50, NULL, 98, 253, 3081, 0.70, 28.7, 655, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2864, '2025-02-01 09:48:15', 1, 9, 7.70, 2.60, 3.30, NULL, 95, 229, 3005, 0.30, 28.7, 699, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2865, '2025-01-31 11:47:15', 1, 9, 7.70, 0.90, 1.30, NULL, 84, 265, 3591, 0.40, 27.5, 716, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2866, '2025-01-30 10:47:15', 1, 9, 7.70, 2.70, 1.20, NULL, 111, 359, 3441, 0.50, 27.8, 735, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2867, '2025-01-29 09:08:15', 1, 9, 7.20, 0.90, 1.90, NULL, 112, 289, 3035, 1.00, 29.2, 701, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2868, '2025-01-28 08:03:15', 1, 9, 7.50, 1.20, 2.60, NULL, 89, 364, 3312, 1.00, 26.8, 780, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2869, '2025-01-27 11:24:15', 1, 9, 7.20, 1.80, 0.50, NULL, 85, 378, 3354, 0.80, 30.0, 663, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2870, '2025-01-26 08:55:15', 1, 9, 7.00, 1.40, 3.50, NULL, 114, 276, 3624, 0.20, 28.9, 738, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2871, '2025-01-25 11:49:15', 1, 9, 7.60, 1.40, 3.20, NULL, 120, 395, 3803, 0.40, 26.8, 663, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2872, '2025-01-24 08:27:15', 1, 9, 7.20, 2.30, 3.50, NULL, 100, 399, 3125, 0.70, 28.6, 778, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2873, '2025-01-23 10:52:15', 1, 9, 7.40, 0.50, 3.40, NULL, 98, 216, 3978, 1.00, 26.6, 745, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2874, '2025-01-22 09:40:15', 1, 9, 7.80, 1.20, 3.50, NULL, 117, 319, 3138, 0.20, 26.9, 768, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2875, '2025-01-21 10:28:15', 1, 9, 7.00, 0.60, 1.90, NULL, 88, 228, 3926, 0.70, 26.5, 759, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2876, '2025-01-20 09:57:15', 1, 9, 7.20, 1.60, 3.10, NULL, 90, 219, 3118, 0.30, 26.9, 656, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2877, '2025-01-19 11:47:15', 1, 9, 7.30, 2.60, 2.80, NULL, 86, 399, 3904, 0.60, 29.3, 720, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2878, '2025-01-18 08:32:15', 1, 9, 7.00, 1.20, 0.80, NULL, 92, 378, 3764, 0.20, 29.5, 774, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2879, '2025-01-17 11:55:15', 1, 9, 7.60, 0.90, 3.10, NULL, 86, 398, 3563, 0.10, 28.7, 699, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2880, '2025-01-16 09:34:15', 1, 9, 7.50, 1.10, 2.60, NULL, 89, 273, 3668, 0.10, 26.3, 694, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2881, '2025-01-15 11:26:15', 1, 9, 7.00, 2.10, 2.60, NULL, 101, 304, 3111, 0.30, 28.9, 792, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2882, '2025-01-14 08:12:15', 1, 9, 7.10, 0.90, 3.00, NULL, 103, 232, 3163, 0.00, 26.6, 687, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2883, '2025-01-13 09:08:15', 1, 9, 7.20, 2.50, 2.60, NULL, 80, 350, 3985, 0.70, 26.1, 754, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2884, '2025-01-12 11:58:15', 1, 9, 7.70, 2.80, 2.90, NULL, 80, 342, 3131, 0.80, 28.4, 713, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2885, '2025-01-11 11:51:15', 1, 9, 7.80, 2.60, 3.40, NULL, 119, 336, 3416, 0.00, 28.7, 774, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2886, '2025-01-10 09:24:15', 1, 9, 7.60, 1.80, 2.60, NULL, 103, 369, 3827, 0.70, 26.5, 685, 'Routine monthly check', '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2887, '2025-01-09 10:05:15', 1, 9, 7.50, 0.70, 1.40, NULL, 98, 299, 3691, 0.00, 26.2, 684, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2888, '2025-01-08 11:08:15', 1, 9, 7.50, 1.10, 1.30, NULL, 81, 399, 3163, 0.30, 26.1, 723, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2889, '2025-01-07 11:41:15', 1, 9, 7.60, 2.90, 2.30, NULL, 93, 300, 3633, 0.10, 26.7, 778, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2890, '2025-01-06 08:41:15', 1, 9, 7.60, 1.70, 2.20, NULL, 113, 238, 3545, 0.30, 29.3, 779, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2891, '2025-01-05 08:57:15', 1, 9, 7.30, 3.00, 3.30, NULL, 110, 261, 3824, 0.40, 27.9, 709, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2892, '2025-01-04 11:13:15', 1, 9, 7.70, 1.90, 1.50, NULL, 115, 240, 3645, 0.90, 27.3, 770, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2893, '2025-01-03 09:04:15', 1, 9, 7.40, 1.20, 2.90, NULL, 84, 211, 3649, 0.00, 27.1, 697, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2894, '2025-01-02 08:50:15', 1, 9, 7.30, 1.60, 2.30, NULL, 115, 328, 3666, 0.90, 26.7, 758, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2895, '2025-01-01 09:19:15', 1, 9, 7.30, 2.30, 2.10, NULL, 97, 309, 3804, 0.20, 26.9, 735, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2896, '2024-12-31 09:25:15', 1, 9, 7.00, 3.00, 3.40, NULL, 107, 333, 3216, 0.30, 29.1, 671, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2897, '2024-12-30 11:54:15', 1, 9, 7.80, 2.40, 0.90, NULL, 80, 337, 3079, 0.20, 27.9, 713, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2898, '2024-12-29 10:38:15', 1, 9, 7.10, 2.90, 2.70, NULL, 108, 344, 3643, 1.00, 27.6, 700, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2899, '2024-12-28 08:55:15', 1, 9, 7.20, 0.80, 2.00, NULL, 88, 310, 3595, 0.70, 26.5, 749, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2900, '2024-12-27 10:36:15', 1, 9, 7.20, 3.00, 1.70, NULL, 83, 327, 3243, 0.80, 26.7, 710, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2901, '2024-12-26 11:20:15', 1, 9, 7.80, 2.20, 2.40, NULL, 107, 283, 3499, 0.30, 26.7, 780, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2902, '2024-12-25 09:41:15', 1, 9, 7.60, 1.00, 2.10, NULL, 118, 207, 3412, 0.60, 26.0, 780, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2903, '2024-12-24 09:22:15', 1, 9, 7.50, 1.00, 1.70, NULL, 91, 380, 3545, 0.30, 29.0, 704, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2904, '2024-12-23 10:17:15', 1, 9, 7.00, 0.90, 3.30, NULL, 110, 256, 3147, 0.70, 28.9, 775, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2905, '2024-12-22 09:05:15', 1, 9, 7.50, 0.50, 1.40, NULL, 88, 226, 3366, 0.70, 26.1, 795, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2906, '2024-12-21 08:21:15', 1, 9, 7.80, 1.90, 1.00, NULL, 88, 338, 3119, 0.40, 27.0, 754, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2907, '2024-12-20 09:34:15', 1, 9, 7.30, 0.80, 3.00, NULL, 89, 250, 3797, 0.30, 26.6, 662, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2908, '2024-12-19 09:08:15', 1, 9, 7.60, 1.30, 1.10, NULL, 82, 231, 3129, 0.00, 26.3, 679, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2909, '2024-12-18 09:29:15', 1, 9, 7.30, 1.80, 2.40, NULL, 85, 347, 3805, 0.80, 29.9, 658, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2910, '2024-12-17 11:00:15', 1, 9, 7.00, 1.70, 2.50, NULL, 117, 357, 3263, 0.70, 29.6, 654, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2911, '2024-12-16 11:03:15', 1, 9, 7.40, 1.10, 1.60, NULL, 82, 397, 3323, 0.70, 29.2, 696, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2912, '2024-12-15 11:18:15', 1, 9, 7.70, 0.70, 2.20, NULL, 83, 319, 3096, 0.70, 26.9, 729, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2913, '2024-12-14 11:19:15', 1, 9, 7.50, 0.60, 1.90, NULL, 92, 363, 3831, 0.30, 28.1, 796, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2914, '2024-12-13 09:00:15', 1, 9, 7.30, 2.00, 0.50, NULL, 118, 279, 3404, 0.00, 29.7, 713, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2915, '2024-12-12 08:11:15', 1, 9, 7.00, 1.50, 1.10, NULL, 100, 300, 3167, 0.70, 29.9, 687, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2916, '2024-12-11 09:37:15', 1, 9, 7.10, 1.20, 2.60, NULL, 117, 244, 3081, 0.30, 28.9, 794, 'Routine monthly check', '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2917, '2024-12-10 11:43:15', 1, 9, 7.50, 1.10, 2.80, NULL, 96, 226, 3947, 0.90, 29.2, 729, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2918, '2024-12-09 10:03:15', 1, 9, 7.60, 0.60, 0.80, NULL, 98, 320, 3623, 0.10, 26.9, 668, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2919, '2024-12-08 09:21:15', 1, 9, 7.30, 0.90, 2.40, NULL, 106, 294, 3369, 0.60, 28.4, 673, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');
INSERT INTO "pool_schema"."pool_water_tests" VALUES (2920, '2024-12-07 11:02:15', 1, 9, 7.50, 0.50, 2.90, NULL, 86, 349, 3020, 0.00, 26.5, 665, NULL, '2025-12-06 12:13:15', '2025-12-06 12:13:15');


--
-- TOC entry 5567 (class 0 OID 43360)
-- Dependencies: 304
-- Data for Name: pool_weekly_tasks; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO "pool_schema"."pool_weekly_tasks" VALUES (1, 1, 1, 49, 2025, true, true, false, true, true, 'Vérification hebdomadaire en cours.', '2025-12-06 12:13:10', '2025-12-06 12:13:10', false, true, '{"technician_id":1,"backwash_done":true,"filter_cleaned":true,"brushing_done":false,"heater_checked":true,"chemical_doser_checked":true,"fittings_retightened":false,"heater_tested":true,"general_inspection_comment":"V\u00e9rification hebdomadaire en cours.","template_id":2}', 2);
INSERT INTO "pool_schema"."pool_weekly_tasks" VALUES (2, 1, 2, 49, 2025, true, true, false, true, true, 'Vérification hebdomadaire en cours.', '2025-12-06 12:13:10', '2025-12-06 12:13:10', false, true, '{"technician_id":1,"backwash_done":true,"filter_cleaned":true,"brushing_done":false,"heater_checked":true,"chemical_doser_checked":true,"fittings_retightened":false,"heater_tested":true,"general_inspection_comment":"V\u00e9rification hebdomadaire en cours.","template_id":2}', 2);
INSERT INTO "pool_schema"."pool_weekly_tasks" VALUES (3, 1, 3, 49, 2025, true, true, false, true, true, 'Vérification hebdomadaire en cours.', '2025-12-06 12:13:10', '2025-12-06 12:13:10', false, true, '{"technician_id":1,"backwash_done":true,"filter_cleaned":true,"brushing_done":false,"heater_checked":true,"chemical_doser_checked":true,"fittings_retightened":false,"heater_tested":true,"general_inspection_comment":"V\u00e9rification hebdomadaire en cours.","template_id":2}', 2);
INSERT INTO "pool_schema"."pool_weekly_tasks" VALUES (4, 1, 4, 49, 2025, true, true, false, true, true, 'Vérification hebdomadaire en cours.', '2025-12-06 12:13:10', '2025-12-06 12:13:10', false, true, '{"technician_id":1,"backwash_done":true,"filter_cleaned":true,"brushing_done":false,"heater_checked":true,"chemical_doser_checked":true,"fittings_retightened":false,"heater_tested":true,"general_inspection_comment":"V\u00e9rification hebdomadaire en cours.","template_id":2}', 2);
INSERT INTO "pool_schema"."pool_weekly_tasks" VALUES (5, 1, 6, 49, 2025, true, true, false, true, true, 'Vérification hebdomadaire en cours.', '2025-12-06 12:13:10', '2025-12-06 12:13:10', false, true, '{"technician_id":1,"backwash_done":true,"filter_cleaned":true,"brushing_done":false,"heater_checked":true,"chemical_doser_checked":true,"fittings_retightened":false,"heater_tested":true,"general_inspection_comment":"V\u00e9rification hebdomadaire en cours.","template_id":2}', 2);
INSERT INTO "pool_schema"."pool_weekly_tasks" VALUES (6, 1, 7, 49, 2025, true, true, false, true, true, 'Vérification hebdomadaire en cours.', '2025-12-06 12:13:10', '2025-12-06 12:13:10', false, true, '{"technician_id":1,"backwash_done":true,"filter_cleaned":true,"brushing_done":false,"heater_checked":true,"chemical_doser_checked":true,"fittings_retightened":false,"heater_tested":true,"general_inspection_comment":"V\u00e9rification hebdomadaire en cours.","template_id":2}', 2);
INSERT INTO "pool_schema"."pool_weekly_tasks" VALUES (7, 1, 8, 49, 2025, true, true, false, true, true, 'Vérification hebdomadaire en cours.', '2025-12-06 12:13:10', '2025-12-06 12:13:10', false, true, '{"technician_id":1,"backwash_done":true,"filter_cleaned":true,"brushing_done":false,"heater_checked":true,"chemical_doser_checked":true,"fittings_retightened":false,"heater_tested":true,"general_inspection_comment":"V\u00e9rification hebdomadaire en cours.","template_id":2}', 2);
INSERT INTO "pool_schema"."pool_weekly_tasks" VALUES (8, 1, 9, 49, 2025, true, true, false, true, true, 'Vérification hebdomadaire en cours.', '2025-12-06 12:13:10', '2025-12-06 12:13:10', false, true, '{"technician_id":1,"backwash_done":true,"filter_cleaned":true,"brushing_done":false,"heater_checked":true,"chemical_doser_checked":true,"fittings_retightened":false,"heater_tested":true,"general_inspection_comment":"V\u00e9rification hebdomadaire en cours.","template_id":2}', 2);


--
-- TOC entry 5549 (class 0 OID 43131)
-- Dependencies: 286
-- Data for Name: product_images; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO "pool_schema"."product_images" VALUES (1, 1, 'images/products/swimwear_men.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (2, 2, 'images/products/swimwear_women.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (3, 3, 'images/products/swim_shorts.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (4, 4, 'images/products/swim_cap.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (5, 5, 'images/products/swimwear_kids.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (6, 6, 'images/products/equipment.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (7, 7, 'images/products/fins.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (8, 8, 'images/products/kickboard.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (9, 9, 'images/products/pull_buoy.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (10, 10, 'images/products/nose_clip.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (11, 11, 'images/products/water_bottle.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (12, 12, 'images/products/drink.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (13, 13, 'images/products/orange_juice.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (14, 14, 'images/products/drink.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (15, 15, 'images/products/water_bottle.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (16, 16, 'images/products/snack.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (17, 17, 'images/products/banana.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (18, 18, 'images/products/nuts_mix.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (19, 19, 'images/products/snack.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (20, 20, 'images/products/sandwich.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (21, 21, 'images/products/accessory.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (22, 22, 'images/products/gym_bag.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (23, 23, 'images/products/shampoo.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (24, 24, 'images/products/shampoo.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."product_images" VALUES (25, 25, 'images/products/accessory.png', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');


--
-- TOC entry 5535 (class 0 OID 42987)
-- Dependencies: 272
-- Data for Name: products; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO "pool_schema"."products" VALUES (1, 1, 'Maillot de bain Homme Pro', 'Maillot de bain de compétition pour homme, résistant au chlore.', 4500.00, 2700.00, 50, 10, 'images/products/swimwear_men.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (2, 1, 'Maillot de bain Femme Elite', 'Maillot une pièce haute performance pour l''entraînement intensif.', 5500.00, 3300.00, 40, 10, 'images/products/swimwear_women.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (3, 1, 'Short de bain Loisir', 'Short confortable pour la nage occasionnelle.', 3000.00, 1800.00, 60, 10, 'images/products/swim_shorts.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (4, 1, 'Bonnet de bain Silicone', 'Bonnet en silicone durable et confortable.', 1200.00, 720.00, 100, 10, 'images/products/swim_cap.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (5, 1, 'Maillot Enfant Junior', 'Maillot coloré et résistant pour les jeunes nageurs.', 2500.00, 1500.00, 30, 10, 'images/products/swimwear_kids.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (6, 2, 'Lunettes de Natation Pro', 'Lunettes anti-buée avec protection UV.', 2800.00, 1680.00, 80, 10, 'images/products/equipment.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (7, 2, 'Palmes Courtes', 'Palmes pour le renforcement musculaire des jambes.', 3500.00, 2100.00, 40, 10, 'images/products/fins.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (8, 2, 'Planche de Natation', 'Accessoire indispensable pour travailler les battements.', 1500.00, 900.00, 50, 10, 'images/products/kickboard.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (9, 2, 'Pull Buoy', 'Flotteur pour isoler le travail des bras.', 1800.00, 1080.00, 45, 10, 'images/products/pull_buoy.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (10, 2, 'Pince-nez', 'Confort optimal pour éviter l''eau dans le nez.', 500.00, 300.00, 120, 10, 'images/products/nose_clip.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (11, 3, 'Eau Minérale 50cl', 'Eau pure et rafraîchissante.', 50.00, 30.00, 200, 10, 'images/products/water_bottle.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (12, 3, 'Boisson Isotonique Sport', 'Pour une récupération optimale après l''effort.', 250.00, 150.00, 100, 10, 'images/products/drink.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (13, 3, 'Jus d''Orange Frais', 'Vitamines naturelles pour le tonus.', 300.00, 180.00, 30, 10, 'images/products/orange_juice.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (14, 3, 'Soda Zéro', 'Boisson gazeuse sans sucre.', 150.00, 90.00, 80, 10, 'images/products/drink.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (15, 3, 'Eau Gazeuse', 'Eau pétillante naturelle.', 80.00, 48.00, 150, 10, 'images/products/water_bottle.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (16, 4, 'Barre Protéinée Choco', '20g de protéines pour la récupération musculaire.', 350.00, 210.00, 100, 10, 'images/products/snack.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (17, 4, 'Banane', 'Fruit frais riche en potassium.', 100.00, 60.00, 50, 10, 'images/products/banana.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (18, 4, 'Mélange de Noix', 'Sachet de noix et fruits secs énergétiques.', 400.00, 240.00, 60, 10, 'images/products/nuts_mix.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (19, 4, 'Yaourt à boire', 'En-cas laitier frais.', 120.00, 72.00, 40, 10, 'images/products/snack.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (20, 4, 'Sandwich Dinde', 'Sandwich frais préparé le jour même.', 450.00, 270.00, 20, 10, 'images/products/sandwich.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (21, 5, 'Serviette Microfibre', 'Séchage ultra-rapide et compacte.', 2000.00, 1200.00, 60, 10, 'images/products/accessory.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (22, 5, 'Sac de Sport', 'Grand sac compartimenté pour vos affaires de piscine.', 4500.00, 2700.00, 25, 10, 'images/products/gym_bag.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (23, 5, 'Shampoing Doux', 'Élimine le chlore et protège les cheveux.', 800.00, 480.00, 50, 10, 'images/products/shampoo.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (24, 5, 'Gel Douche Sport', 'Rafraîchissant et énergisant.', 700.00, 420.00, 55, 10, 'images/products/shampoo.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."products" VALUES (25, 5, 'Cadenas Casier', 'Pour sécuriser vos effets personnels.', 600.00, 360.00, 100, 10, 'images/products/accessory.png', '2025-12-06 12:13:10', '2025-12-06 12:13:10');


--
-- TOC entry 5507 (class 0 OID 42698)
-- Dependencies: 244
-- Data for Name: role_permissions; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO "pool_schema"."role_permissions" VALUES (1, 1, 25);
INSERT INTO "pool_schema"."role_permissions" VALUES (2, 1, 29);
INSERT INTO "pool_schema"."role_permissions" VALUES (3, 7, 31);
INSERT INTO "pool_schema"."role_permissions" VALUES (4, 7, 32);
INSERT INTO "pool_schema"."role_permissions" VALUES (5, 7, 33);
INSERT INTO "pool_schema"."role_permissions" VALUES (6, 7, 34);
INSERT INTO "pool_schema"."role_permissions" VALUES (7, 7, 35);
INSERT INTO "pool_schema"."role_permissions" VALUES (8, 7, 36);
INSERT INTO "pool_schema"."role_permissions" VALUES (9, 7, 37);
INSERT INTO "pool_schema"."role_permissions" VALUES (10, 2, 31);
INSERT INTO "pool_schema"."role_permissions" VALUES (11, 2, 32);
INSERT INTO "pool_schema"."role_permissions" VALUES (12, 2, 33);
INSERT INTO "pool_schema"."role_permissions" VALUES (13, 2, 34);
INSERT INTO "pool_schema"."role_permissions" VALUES (14, 2, 35);
INSERT INTO "pool_schema"."role_permissions" VALUES (15, 2, 36);
INSERT INTO "pool_schema"."role_permissions" VALUES (16, 2, 37);
INSERT INTO "pool_schema"."role_permissions" VALUES (17, 3, 31);
INSERT INTO "pool_schema"."role_permissions" VALUES (18, 3, 32);
INSERT INTO "pool_schema"."role_permissions" VALUES (19, 3, 35);
INSERT INTO "pool_schema"."role_permissions" VALUES (20, 3, 36);
INSERT INTO "pool_schema"."role_permissions" VALUES (21, 3, 37);


--
-- TOC entry 5505 (class 0 OID 42687)
-- Dependencies: 242
-- Data for Name: roles; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO "pool_schema"."roles" VALUES (1, 'coach');
INSERT INTO "pool_schema"."roles" VALUES (2, 'maintenance_manager');
INSERT INTO "pool_schema"."roles" VALUES (3, 'pool_technician');
INSERT INTO "pool_schema"."roles" VALUES (4, 'directeur');
INSERT INTO "pool_schema"."roles" VALUES (5, 'financer');
INSERT INTO "pool_schema"."roles" VALUES (6, 'réceptionniste');
INSERT INTO "pool_schema"."roles" VALUES (7, 'admin');


--
-- TOC entry 5539 (class 0 OID 43032)
-- Dependencies: 276
-- Data for Name: sale_items; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5537 (class 0 OID 43011)
-- Dependencies: 274
-- Data for Name: sales; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5494 (class 0 OID 42594)
-- Dependencies: 231
-- Data for Name: sessions; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO "pool_schema"."sessions" VALUES ('ZNmKukS2OOkAEJSKhOIISj5yCc9z0FRH9j0SzYyn', 5, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiYUs2ak1zdWVlOXA2RkZGQ0VvanhQdnRZY1NVYzlQM05OeHNEbXV4WiI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyNzoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2FkbWluIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MTI3OiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYWRtaW4vc2NoZWR1bGUvZXZlbnRzP2VuZD0yMDI1LTEyLTA4VDAwJTNBMDAlM0EwMCZzdGFydD0yMDI1LTEyLTAxVDAwJTNBMDAlM0EwMCZ0aW1lWm9uZT1BZnJpY2ElMkZBbGdpZXJzIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NTt9', 1765019901);


--
-- TOC entry 5509 (class 0 OID 42718)
-- Dependencies: 246
-- Data for Name: staff; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO "pool_schema"."staff" VALUES (1, 'Ali', 'Ben', 'directeur', '$2y$12$Dm3tgGUcN8AkT8Zcd9gxO.uR/6ZznRd6cQNDJFn5bhGpSBnJQTHJi', 4, true, '2025-12-06 12:13:08', NULL, NULL, NULL, NULL, NULL, 'per_hour', 0.00, NULL);
INSERT INTO "pool_schema"."staff" VALUES (2, 'Sara', 'Fin', 'financer', '$2y$12$uYrQkNd5Ua0bcFw09ijm/uk0JxCjKSjNPymzLdAKNFIRlm1BTYAeW', 5, true, '2025-12-06 12:13:08', NULL, NULL, NULL, NULL, NULL, 'per_hour', 0.00, NULL);
INSERT INTO "pool_schema"."staff" VALUES (3, 'Mounir', 'Fix', 'maintenance', '$2y$12$M39XL/fT1CoHLtiBpuDr.eLOkvI6cI5J1TLVuWJ0IY.WWHWjniEeK', 2, true, '2025-12-06 12:13:09', NULL, NULL, NULL, NULL, NULL, 'per_hour', 0.00, NULL);
INSERT INTO "pool_schema"."staff" VALUES (4, 'Lina', 'Rec', 'reception', '$2y$12$.z8BS118pDBBBg8OFQ9.ae5Q7PPh7NPdsvhfjr1I3RJpDBdSG7zaK', 6, true, '2025-12-06 12:13:09', NULL, NULL, NULL, NULL, NULL, 'per_hour', 0.00, NULL);
INSERT INTO "pool_schema"."staff" VALUES (5, 'Admin', 'User', 'admin', '$2y$12$269oto5oIn3i/Zfnh1fg5uolEf1wMQbPYsQo061zSCwBZzPox2lWm', 7, true, '2025-12-06 12:13:09', NULL, NULL, NULL, NULL, NULL, 'per_hour', 0.00, NULL);


--
-- TOC entry 5543 (class 0 OID 43076)
-- Dependencies: 280
-- Data for Name: staff_leaves; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5541 (class 0 OID 43055)
-- Dependencies: 278
-- Data for Name: staff_schedules; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5529 (class 0 OID 42935)
-- Dependencies: 266
-- Data for Name: subscription_allowed_days; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5521 (class 0 OID 42831)
-- Dependencies: 258
-- Data for Name: subscriptions; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5571 (class 0 OID 43434)
-- Dependencies: 308
-- Data for Name: task_templates; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO "pool_schema"."task_templates" VALUES (1, 'Check-list Quotidienne Standard', 'daily', '[{"label":"Skimmers Nettoy\u00e9s","type":"checkbox","key":"skimmer_cleaned"},{"label":"Fond Aspir\u00e9","type":"checkbox","key":"vacuum_done"},{"label":"D\u00e9bris Retir\u00e9s","type":"checkbox","key":"debris_removed"},{"label":"Bondes V\u00e9rifi\u00e9es","type":"checkbox","key":"drains_checked"},{"label":"Grilles Anti-Noyade","type":"checkbox","key":"drain_covers_inspected"},{"label":"\u00c9clairage OK","type":"checkbox","key":"lighting_checked"},{"label":"Test Clart\u00e9 Visuelle","type":"checkbox","key":"clarity_test_passed"},{"label":"Pression (bar)","type":"number","key":"pressure_reading","step":"0.1"},{"label":"\u00c9tat Pompe","type":"select","key":"pump_status","options":{"ok":"OK","noisy":"Bruyante","off":"Arr\u00eat"}},{"label":"Anomalies \/ Notes","type":"textarea","key":"anomalies_comment"}]', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."task_templates" VALUES (2, 'Check-list Hebdomadaire Standard', 'weekly', '[{"label":"Brossage Complet","type":"checkbox","key":"brushing_done"},{"label":"Lavage Filtre (Backwash)","type":"checkbox","key":"backwash_done"},{"label":"Nettoyage Pr\u00e9filtre","type":"checkbox","key":"filter_cleaned"},{"label":"Resserrage Raccords","type":"checkbox","key":"fittings_retightened"},{"label":"Test Chauffage","type":"checkbox","key":"heater_tested"},{"label":"Contr\u00f4le Doseuse","type":"checkbox","key":"chemical_doser_checked"},{"label":"Inspection G\u00e9n\u00e9rale","type":"textarea","key":"general_inspection_comment"}]', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');
INSERT INTO "pool_schema"."task_templates" VALUES (3, 'Check-list Mensuelle Standard', 'monthly', '[{"label":"Remplacement Eau (Partiel)","type":"checkbox","key":"water_replacement_partial"},{"label":"Inspection Compl\u00e8te Syst\u00e8me","type":"checkbox","key":"full_system_inspection"},{"label":"Calibration Dosage Chimique","type":"checkbox","key":"chemical_dosing_calibration"},{"label":"Notes \/ Observations","type":"textarea","key":"notes"}]', true, '2025-12-06 12:13:10', '2025-12-06 12:13:10');


--
-- TOC entry 5515 (class 0 OID 42765)
-- Dependencies: 252
-- Data for Name: time_slots; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5492 (class 0 OID 42571)
-- Dependencies: 229
-- Data for Name: users; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5511 (class 0 OID 42741)
-- Dependencies: 248
-- Data for Name: weekdays; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5617 (class 0 OID 0)
-- Dependencies: 261
-- Name: access_badges_badge_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."access_badges_badge_id_seq"', 20, true);


--
-- TOC entry 5618 (class 0 OID 0)
-- Dependencies: 263
-- Name: access_logs_log_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."access_logs_log_id_seq"', 1, false);


--
-- TOC entry 5619 (class 0 OID 0)
-- Dependencies: 249
-- Name: activities_activity_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."activities_activity_id_seq"', 1, false);


--
-- TOC entry 5620 (class 0 OID 0)
-- Dependencies: 267
-- Name: activity_plan_prices_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."activity_plan_prices_id_seq"', 1, false);


--
-- TOC entry 5621 (class 0 OID 0)
-- Dependencies: 269
-- Name: categories_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."categories_id_seq"', 5, true);


--
-- TOC entry 5622 (class 0 OID 0)
-- Dependencies: 283
-- Name: coach_time_slot_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."coach_time_slot_id_seq"', 1, false);


--
-- TOC entry 5623 (class 0 OID 0)
-- Dependencies: 281
-- Name: expenses_expense_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."expenses_expense_id_seq"', 1, false);


--
-- TOC entry 5624 (class 0 OID 0)
-- Dependencies: 287
-- Name: facilities_facility_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."facilities_facility_id_seq"', 9, true);


--
-- TOC entry 5625 (class 0 OID 0)
-- Dependencies: 237
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."failed_jobs_id_seq"', 1, false);


--
-- TOC entry 5626 (class 0 OID 0)
-- Dependencies: 234
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."jobs_id_seq"', 1, false);


--
-- TOC entry 5627 (class 0 OID 0)
-- Dependencies: 255
-- Name: members_member_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."members_member_id_seq"', 1, false);


--
-- TOC entry 5628 (class 0 OID 0)
-- Dependencies: 226
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."migrations_id_seq"', 20, true);


--
-- TOC entry 5629 (class 0 OID 0)
-- Dependencies: 259
-- Name: payments_payment_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."payments_payment_id_seq"', 1, false);


--
-- TOC entry 5630 (class 0 OID 0)
-- Dependencies: 239
-- Name: permissions_permission_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."permissions_permission_id_seq"', 37, true);


--
-- TOC entry 5631 (class 0 OID 0)
-- Dependencies: 253
-- Name: plans_plan_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."plans_plan_id_seq"', 3, true);


--
-- TOC entry 5632 (class 0 OID 0)
-- Dependencies: 293
-- Name: pool_chemical_stock_chemical_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."pool_chemical_stock_chemical_id_seq"', 5, true);


--
-- TOC entry 5633 (class 0 OID 0)
-- Dependencies: 295
-- Name: pool_chemical_usage_usage_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."pool_chemical_usage_usage_id_seq"', 1, false);


--
-- TOC entry 5634 (class 0 OID 0)
-- Dependencies: 301
-- Name: pool_daily_tasks_task_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."pool_daily_tasks_task_id_seq"', 16, true);


--
-- TOC entry 5635 (class 0 OID 0)
-- Dependencies: 289
-- Name: pool_equipment_equipment_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."pool_equipment_equipment_id_seq"', 4, true);


--
-- TOC entry 5636 (class 0 OID 0)
-- Dependencies: 297
-- Name: pool_equipment_maintenance_maintenance_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."pool_equipment_maintenance_maintenance_id_seq"', 1, false);


--
-- TOC entry 5637 (class 0 OID 0)
-- Dependencies: 299
-- Name: pool_incidents_incident_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."pool_incidents_incident_id_seq"', 2, true);


--
-- TOC entry 5638 (class 0 OID 0)
-- Dependencies: 305
-- Name: pool_monthly_tasks_monthly_task_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."pool_monthly_tasks_monthly_task_id_seq"', 8, true);


--
-- TOC entry 5639 (class 0 OID 0)
-- Dependencies: 291
-- Name: pool_water_tests_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."pool_water_tests_id_seq"', 2920, true);


--
-- TOC entry 5640 (class 0 OID 0)
-- Dependencies: 303
-- Name: pool_weekly_tasks_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."pool_weekly_tasks_id_seq"', 8, true);


--
-- TOC entry 5641 (class 0 OID 0)
-- Dependencies: 285
-- Name: product_images_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."product_images_id_seq"', 25, true);


--
-- TOC entry 5642 (class 0 OID 0)
-- Dependencies: 271
-- Name: products_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."products_id_seq"', 25, true);


--
-- TOC entry 5643 (class 0 OID 0)
-- Dependencies: 243
-- Name: role_permissions_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."role_permissions_id_seq"', 21, true);


--
-- TOC entry 5644 (class 0 OID 0)
-- Dependencies: 241
-- Name: roles_role_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."roles_role_id_seq"', 7, true);


--
-- TOC entry 5645 (class 0 OID 0)
-- Dependencies: 275
-- Name: sale_items_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."sale_items_id_seq"', 1, false);


--
-- TOC entry 5646 (class 0 OID 0)
-- Dependencies: 273
-- Name: sales_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."sales_id_seq"', 1, false);


--
-- TOC entry 5647 (class 0 OID 0)
-- Dependencies: 279
-- Name: staff_leaves_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."staff_leaves_id_seq"', 1, false);


--
-- TOC entry 5648 (class 0 OID 0)
-- Dependencies: 277
-- Name: staff_schedules_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."staff_schedules_id_seq"', 1, false);


--
-- TOC entry 5649 (class 0 OID 0)
-- Dependencies: 245
-- Name: staff_staff_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."staff_staff_id_seq"', 5, true);


--
-- TOC entry 5650 (class 0 OID 0)
-- Dependencies: 265
-- Name: subscription_allowed_days_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."subscription_allowed_days_id_seq"', 1, false);


--
-- TOC entry 5651 (class 0 OID 0)
-- Dependencies: 257
-- Name: subscriptions_subscription_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."subscriptions_subscription_id_seq"', 1, false);


--
-- TOC entry 5652 (class 0 OID 0)
-- Dependencies: 307
-- Name: task_templates_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."task_templates_id_seq"', 3, true);


--
-- TOC entry 5653 (class 0 OID 0)
-- Dependencies: 251
-- Name: time_slots_slot_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."time_slots_slot_id_seq"', 1, false);


--
-- TOC entry 5654 (class 0 OID 0)
-- Dependencies: 228
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."users_id_seq"', 1, false);


--
-- TOC entry 5655 (class 0 OID 0)
-- Dependencies: 247
-- Name: weekdays_weekday_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('"pool_schema"."weekdays_weekday_id_seq"', 1, false);


--
-- TOC entry 5238 (class 2606 OID 42909)
-- Name: access_badges access_badges_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."access_badges"
    ADD CONSTRAINT "access_badges_pkey" PRIMARY KEY ("badge_id");


--
-- TOC entry 5242 (class 2606 OID 42928)
-- Name: access_logs access_logs_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."access_logs"
    ADD CONSTRAINT "access_logs_pkey" PRIMARY KEY ("log_id");


--
-- TOC entry 5226 (class 2606 OID 42763)
-- Name: activities activities_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."activities"
    ADD CONSTRAINT "activities_pkey" PRIMARY KEY ("activity_id");


--
-- TOC entry 5246 (class 2606 OID 42964)
-- Name: activity_plan_prices activity_plan_prices_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."activity_plan_prices"
    ADD CONSTRAINT "activity_plan_prices_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5197 (class 2606 OID 42625)
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."cache_locks"
    ADD CONSTRAINT "cache_locks_pkey" PRIMARY KEY ("key");


--
-- TOC entry 5195 (class 2606 OID 42615)
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."cache"
    ADD CONSTRAINT "cache_pkey" PRIMARY KEY ("key");


--
-- TOC entry 5248 (class 2606 OID 42985)
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."categories"
    ADD CONSTRAINT "categories_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5262 (class 2606 OID 43129)
-- Name: coach_time_slot coach_time_slot_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."coach_time_slot"
    ADD CONSTRAINT "coach_time_slot_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5260 (class 2606 OID 43111)
-- Name: expenses expenses_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."expenses"
    ADD CONSTRAINT "expenses_pkey" PRIMARY KEY ("expense_id");


--
-- TOC entry 5266 (class 2606 OID 43180)
-- Name: facilities facilities_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."facilities"
    ADD CONSTRAINT "facilities_pkey" PRIMARY KEY ("facility_id");


--
-- TOC entry 5204 (class 2606 OID 42672)
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."failed_jobs"
    ADD CONSTRAINT "failed_jobs_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5206 (class 2606 OID 42674)
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."failed_jobs"
    ADD CONSTRAINT "failed_jobs_uuid_unique" UNIQUE ("uuid");


--
-- TOC entry 5202 (class 2606 OID 42655)
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."job_batches"
    ADD CONSTRAINT "job_batches_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5199 (class 2606 OID 42640)
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."jobs"
    ADD CONSTRAINT "jobs_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5232 (class 2606 OID 42819)
-- Name: members members_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."members"
    ADD CONSTRAINT "members_pkey" PRIMARY KEY ("member_id");


--
-- TOC entry 5183 (class 2606 OID 42569)
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."migrations"
    ADD CONSTRAINT "migrations_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5189 (class 2606 OID 42593)
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."password_reset_tokens"
    ADD CONSTRAINT "password_reset_tokens_pkey" PRIMARY KEY ("email");


--
-- TOC entry 5236 (class 2606 OID 42886)
-- Name: payments payments_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."payments"
    ADD CONSTRAINT "payments_pkey" PRIMARY KEY ("payment_id");


--
-- TOC entry 5208 (class 2606 OID 42683)
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."permissions"
    ADD CONSTRAINT "permissions_pkey" PRIMARY KEY ("permission_id");


--
-- TOC entry 5230 (class 2606 OID 42807)
-- Name: plans plans_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."plans"
    ADD CONSTRAINT "plans_pkey" PRIMARY KEY ("plan_id");


--
-- TOC entry 5272 (class 2606 OID 43236)
-- Name: pool_chemical_stock pool_chemical_stock_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_chemical_stock"
    ADD CONSTRAINT "pool_chemical_stock_pkey" PRIMARY KEY ("chemical_id");


--
-- TOC entry 5274 (class 2606 OID 43250)
-- Name: pool_chemical_usage pool_chemical_usage_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_chemical_usage"
    ADD CONSTRAINT "pool_chemical_usage_pkey" PRIMARY KEY ("usage_id");


--
-- TOC entry 5280 (class 2606 OID 43348)
-- Name: pool_daily_tasks pool_daily_tasks_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_daily_tasks"
    ADD CONSTRAINT "pool_daily_tasks_pkey" PRIMARY KEY ("task_id");


--
-- TOC entry 5276 (class 2606 OID 43281)
-- Name: pool_equipment_maintenance pool_equipment_maintenance_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_equipment_maintenance"
    ADD CONSTRAINT "pool_equipment_maintenance_pkey" PRIMARY KEY ("maintenance_id");


--
-- TOC entry 5268 (class 2606 OID 43194)
-- Name: pool_equipment pool_equipment_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_equipment"
    ADD CONSTRAINT "pool_equipment_pkey" PRIMARY KEY ("equipment_id");


--
-- TOC entry 5278 (class 2606 OID 43307)
-- Name: pool_incidents pool_incidents_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_incidents"
    ADD CONSTRAINT "pool_incidents_pkey" PRIMARY KEY ("incident_id");


--
-- TOC entry 5284 (class 2606 OID 43422)
-- Name: pool_monthly_tasks pool_monthly_tasks_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_monthly_tasks"
    ADD CONSTRAINT "pool_monthly_tasks_pkey" PRIMARY KEY ("monthly_task_id");


--
-- TOC entry 5240 (class 2606 OID 42916)
-- Name: access_badges pool_schema_access_badges_badge_uid_unique; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."access_badges"
    ADD CONSTRAINT "pool_schema_access_badges_badge_uid_unique" UNIQUE ("badge_uid");


--
-- TOC entry 5210 (class 2606 OID 42685)
-- Name: permissions pool_schema_permissions_permission_name_unique; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."permissions"
    ADD CONSTRAINT "pool_schema_permissions_permission_name_unique" UNIQUE ("permission_name");


--
-- TOC entry 5212 (class 2606 OID 42696)
-- Name: roles pool_schema_roles_role_name_unique; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."roles"
    ADD CONSTRAINT "pool_schema_roles_role_name_unique" UNIQUE ("role_name");


--
-- TOC entry 5218 (class 2606 OID 42739)
-- Name: staff pool_schema_staff_username_unique; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."staff"
    ADD CONSTRAINT "pool_schema_staff_username_unique" UNIQUE ("username");


--
-- TOC entry 5222 (class 2606 OID 42750)
-- Name: weekdays pool_schema_weekdays_day_name_unique; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."weekdays"
    ADD CONSTRAINT "pool_schema_weekdays_day_name_unique" UNIQUE ("day_name");


--
-- TOC entry 5270 (class 2606 OID 43207)
-- Name: pool_water_tests pool_water_tests_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_water_tests"
    ADD CONSTRAINT "pool_water_tests_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5282 (class 2606 OID 43382)
-- Name: pool_weekly_tasks pool_weekly_tasks_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_weekly_tasks"
    ADD CONSTRAINT "pool_weekly_tasks_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5264 (class 2606 OID 43141)
-- Name: product_images product_images_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."product_images"
    ADD CONSTRAINT "product_images_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5250 (class 2606 OID 43004)
-- Name: products products_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."products"
    ADD CONSTRAINT "products_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5216 (class 2606 OID 42706)
-- Name: role_permissions role_permissions_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."role_permissions"
    ADD CONSTRAINT "role_permissions_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5214 (class 2606 OID 42694)
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."roles"
    ADD CONSTRAINT "roles_pkey" PRIMARY KEY ("role_id");


--
-- TOC entry 5254 (class 2606 OID 43043)
-- Name: sale_items sale_items_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."sale_items"
    ADD CONSTRAINT "sale_items_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5252 (class 2606 OID 43020)
-- Name: sales sales_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."sales"
    ADD CONSTRAINT "sales_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5192 (class 2606 OID 42603)
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."sessions"
    ADD CONSTRAINT "sessions_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5258 (class 2606 OID 43090)
-- Name: staff_leaves staff_leaves_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."staff_leaves"
    ADD CONSTRAINT "staff_leaves_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5220 (class 2606 OID 42732)
-- Name: staff staff_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."staff"
    ADD CONSTRAINT "staff_pkey" PRIMARY KEY ("staff_id");


--
-- TOC entry 5256 (class 2606 OID 43069)
-- Name: staff_schedules staff_schedules_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."staff_schedules"
    ADD CONSTRAINT "staff_schedules_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5244 (class 2606 OID 42943)
-- Name: subscription_allowed_days subscription_allowed_days_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscription_allowed_days"
    ADD CONSTRAINT "subscription_allowed_days_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5234 (class 2606 OID 42843)
-- Name: subscriptions subscriptions_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscriptions"
    ADD CONSTRAINT "subscriptions_pkey" PRIMARY KEY ("subscription_id");


--
-- TOC entry 5286 (class 2606 OID 43448)
-- Name: task_templates task_templates_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."task_templates"
    ADD CONSTRAINT "task_templates_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5228 (class 2606 OID 42778)
-- Name: time_slots time_slots_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."time_slots"
    ADD CONSTRAINT "time_slots_pkey" PRIMARY KEY ("slot_id");


--
-- TOC entry 5185 (class 2606 OID 42584)
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."users"
    ADD CONSTRAINT "users_email_unique" UNIQUE ("email");


--
-- TOC entry 5187 (class 2606 OID 42582)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."users"
    ADD CONSTRAINT "users_pkey" PRIMARY KEY ("id");


--
-- TOC entry 5224 (class 2606 OID 42748)
-- Name: weekdays weekdays_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."weekdays"
    ADD CONSTRAINT "weekdays_pkey" PRIMARY KEY ("weekday_id");


--
-- TOC entry 5200 (class 1259 OID 42641)
-- Name: jobs_queue_index; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX "jobs_queue_index" ON "pool_schema"."jobs" USING "btree" ("queue");


--
-- TOC entry 5190 (class 1259 OID 42605)
-- Name: sessions_last_activity_index; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX "sessions_last_activity_index" ON "pool_schema"."sessions" USING "btree" ("last_activity");


--
-- TOC entry 5193 (class 1259 OID 42604)
-- Name: sessions_user_id_index; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX "sessions_user_id_index" ON "pool_schema"."sessions" USING "btree" ("user_id");


--
-- TOC entry 5305 (class 2606 OID 42910)
-- Name: access_badges pool_schema_access_badges_member_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."access_badges"
    ADD CONSTRAINT "pool_schema_access_badges_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "pool_schema"."members"("member_id") ON DELETE CASCADE;


--
-- TOC entry 5306 (class 2606 OID 43156)
-- Name: access_badges pool_schema_access_badges_staff_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."access_badges"
    ADD CONSTRAINT "pool_schema_access_badges_staff_id_foreign" FOREIGN KEY ("staff_id") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE CASCADE;


--
-- TOC entry 5307 (class 2606 OID 42929)
-- Name: access_logs pool_schema_access_logs_member_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."access_logs"
    ADD CONSTRAINT "pool_schema_access_logs_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "pool_schema"."members"("member_id") ON DELETE CASCADE;


--
-- TOC entry 5308 (class 2606 OID 43161)
-- Name: access_logs pool_schema_access_logs_staff_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."access_logs"
    ADD CONSTRAINT "pool_schema_access_logs_staff_id_foreign" FOREIGN KEY ("staff_id") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE CASCADE;


--
-- TOC entry 5311 (class 2606 OID 42965)
-- Name: activity_plan_prices pool_schema_activity_plan_prices_activity_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."activity_plan_prices"
    ADD CONSTRAINT "pool_schema_activity_plan_prices_activity_id_foreign" FOREIGN KEY ("activity_id") REFERENCES "pool_schema"."activities"("activity_id") ON DELETE CASCADE;


--
-- TOC entry 5312 (class 2606 OID 42970)
-- Name: activity_plan_prices pool_schema_activity_plan_prices_plan_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."activity_plan_prices"
    ADD CONSTRAINT "pool_schema_activity_plan_prices_plan_id_foreign" FOREIGN KEY ("plan_id") REFERENCES "pool_schema"."plans"("plan_id") ON DELETE CASCADE;


--
-- TOC entry 5320 (class 2606 OID 43112)
-- Name: expenses pool_schema_expenses_created_by_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."expenses"
    ADD CONSTRAINT "pool_schema_expenses_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE SET NULL;


--
-- TOC entry 5295 (class 2606 OID 42820)
-- Name: members pool_schema_members_created_by_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."members"
    ADD CONSTRAINT "pool_schema_members_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE SET NULL;


--
-- TOC entry 5296 (class 2606 OID 42825)
-- Name: members pool_schema_members_updated_by_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."members"
    ADD CONSTRAINT "pool_schema_members_updated_by_foreign" FOREIGN KEY ("updated_by") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE SET NULL;


--
-- TOC entry 5303 (class 2606 OID 42892)
-- Name: payments pool_schema_payments_received_by_staff_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."payments"
    ADD CONSTRAINT "pool_schema_payments_received_by_staff_id_foreign" FOREIGN KEY ("received_by_staff_id") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE SET NULL;


--
-- TOC entry 5304 (class 2606 OID 42887)
-- Name: payments pool_schema_payments_subscription_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."payments"
    ADD CONSTRAINT "pool_schema_payments_subscription_id_foreign" FOREIGN KEY ("subscription_id") REFERENCES "pool_schema"."subscriptions"("subscription_id") ON DELETE CASCADE;


--
-- TOC entry 5324 (class 2606 OID 43251)
-- Name: pool_chemical_usage pool_schema_pool_chemical_usage_chemical_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_chemical_usage"
    ADD CONSTRAINT "pool_schema_pool_chemical_usage_chemical_id_foreign" FOREIGN KEY ("chemical_id") REFERENCES "pool_schema"."pool_chemical_stock"("chemical_id");


--
-- TOC entry 5325 (class 2606 OID 43261)
-- Name: pool_chemical_usage pool_schema_pool_chemical_usage_related_test_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_chemical_usage"
    ADD CONSTRAINT "pool_schema_pool_chemical_usage_related_test_id_foreign" FOREIGN KEY ("related_test_id") REFERENCES "pool_schema"."pool_water_tests"("id") ON DELETE SET NULL;


--
-- TOC entry 5326 (class 2606 OID 43256)
-- Name: pool_chemical_usage pool_schema_pool_chemical_usage_technician_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_chemical_usage"
    ADD CONSTRAINT "pool_schema_pool_chemical_usage_technician_id_foreign" FOREIGN KEY ("technician_id") REFERENCES "pool_schema"."staff"("staff_id");


--
-- TOC entry 5333 (class 2606 OID 43354)
-- Name: pool_daily_tasks pool_schema_pool_daily_tasks_pool_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_daily_tasks"
    ADD CONSTRAINT "pool_schema_pool_daily_tasks_pool_id_foreign" FOREIGN KEY ("pool_id") REFERENCES "pool_schema"."facilities"("facility_id");


--
-- TOC entry 5334 (class 2606 OID 43349)
-- Name: pool_daily_tasks pool_schema_pool_daily_tasks_technician_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_daily_tasks"
    ADD CONSTRAINT "pool_schema_pool_daily_tasks_technician_id_foreign" FOREIGN KEY ("technician_id") REFERENCES "pool_schema"."staff"("staff_id");


--
-- TOC entry 5335 (class 2606 OID 43449)
-- Name: pool_daily_tasks pool_schema_pool_daily_tasks_template_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_daily_tasks"
    ADD CONSTRAINT "pool_schema_pool_daily_tasks_template_id_foreign" FOREIGN KEY ("template_id") REFERENCES "pool_schema"."task_templates"("id") ON DELETE SET NULL;


--
-- TOC entry 5327 (class 2606 OID 43282)
-- Name: pool_equipment_maintenance pool_schema_pool_equipment_maintenance_equipment_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_equipment_maintenance"
    ADD CONSTRAINT "pool_schema_pool_equipment_maintenance_equipment_id_foreign" FOREIGN KEY ("equipment_id") REFERENCES "pool_schema"."pool_equipment"("equipment_id");


--
-- TOC entry 5328 (class 2606 OID 43287)
-- Name: pool_equipment_maintenance pool_schema_pool_equipment_maintenance_technician_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_equipment_maintenance"
    ADD CONSTRAINT "pool_schema_pool_equipment_maintenance_technician_id_foreign" FOREIGN KEY ("technician_id") REFERENCES "pool_schema"."staff"("staff_id");


--
-- TOC entry 5329 (class 2606 OID 43323)
-- Name: pool_incidents pool_schema_pool_incidents_assigned_to_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_incidents"
    ADD CONSTRAINT "pool_schema_pool_incidents_assigned_to_foreign" FOREIGN KEY ("assigned_to") REFERENCES "pool_schema"."staff"("staff_id");


--
-- TOC entry 5330 (class 2606 OID 43318)
-- Name: pool_incidents pool_schema_pool_incidents_created_by_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_incidents"
    ADD CONSTRAINT "pool_schema_pool_incidents_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "pool_schema"."staff"("staff_id");


--
-- TOC entry 5331 (class 2606 OID 43308)
-- Name: pool_incidents pool_schema_pool_incidents_equipment_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_incidents"
    ADD CONSTRAINT "pool_schema_pool_incidents_equipment_id_foreign" FOREIGN KEY ("equipment_id") REFERENCES "pool_schema"."pool_equipment"("equipment_id");


--
-- TOC entry 5332 (class 2606 OID 43313)
-- Name: pool_incidents pool_schema_pool_incidents_pool_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_incidents"
    ADD CONSTRAINT "pool_schema_pool_incidents_pool_id_foreign" FOREIGN KEY ("pool_id") REFERENCES "pool_schema"."facilities"("facility_id");


--
-- TOC entry 5339 (class 2606 OID 43423)
-- Name: pool_monthly_tasks pool_schema_pool_monthly_tasks_facility_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_monthly_tasks"
    ADD CONSTRAINT "pool_schema_pool_monthly_tasks_facility_id_foreign" FOREIGN KEY ("facility_id") REFERENCES "pool_schema"."facilities"("facility_id") ON DELETE CASCADE;


--
-- TOC entry 5340 (class 2606 OID 43428)
-- Name: pool_monthly_tasks pool_schema_pool_monthly_tasks_technician_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_monthly_tasks"
    ADD CONSTRAINT "pool_schema_pool_monthly_tasks_technician_id_foreign" FOREIGN KEY ("technician_id") REFERENCES "pool_schema"."staff"("staff_id");


--
-- TOC entry 5341 (class 2606 OID 43459)
-- Name: pool_monthly_tasks pool_schema_pool_monthly_tasks_template_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_monthly_tasks"
    ADD CONSTRAINT "pool_schema_pool_monthly_tasks_template_id_foreign" FOREIGN KEY ("template_id") REFERENCES "pool_schema"."task_templates"("id") ON DELETE SET NULL;


--
-- TOC entry 5322 (class 2606 OID 43213)
-- Name: pool_water_tests pool_schema_pool_water_tests_pool_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_water_tests"
    ADD CONSTRAINT "pool_schema_pool_water_tests_pool_id_foreign" FOREIGN KEY ("pool_id") REFERENCES "pool_schema"."facilities"("facility_id");


--
-- TOC entry 5323 (class 2606 OID 43208)
-- Name: pool_water_tests pool_schema_pool_water_tests_technician_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_water_tests"
    ADD CONSTRAINT "pool_schema_pool_water_tests_technician_id_foreign" FOREIGN KEY ("technician_id") REFERENCES "pool_schema"."staff"("staff_id");


--
-- TOC entry 5336 (class 2606 OID 43388)
-- Name: pool_weekly_tasks pool_schema_pool_weekly_tasks_pool_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_weekly_tasks"
    ADD CONSTRAINT "pool_schema_pool_weekly_tasks_pool_id_foreign" FOREIGN KEY ("pool_id") REFERENCES "pool_schema"."facilities"("facility_id");


--
-- TOC entry 5337 (class 2606 OID 43383)
-- Name: pool_weekly_tasks pool_schema_pool_weekly_tasks_technician_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_weekly_tasks"
    ADD CONSTRAINT "pool_schema_pool_weekly_tasks_technician_id_foreign" FOREIGN KEY ("technician_id") REFERENCES "pool_schema"."staff"("staff_id");


--
-- TOC entry 5338 (class 2606 OID 43454)
-- Name: pool_weekly_tasks pool_schema_pool_weekly_tasks_template_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."pool_weekly_tasks"
    ADD CONSTRAINT "pool_schema_pool_weekly_tasks_template_id_foreign" FOREIGN KEY ("template_id") REFERENCES "pool_schema"."task_templates"("id") ON DELETE SET NULL;


--
-- TOC entry 5313 (class 2606 OID 43005)
-- Name: products pool_schema_products_category_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."products"
    ADD CONSTRAINT "pool_schema_products_category_id_foreign" FOREIGN KEY ("category_id") REFERENCES "pool_schema"."categories"("id") ON DELETE CASCADE;


--
-- TOC entry 5287 (class 2606 OID 42712)
-- Name: role_permissions pool_schema_role_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."role_permissions"
    ADD CONSTRAINT "pool_schema_role_permissions_permission_id_foreign" FOREIGN KEY ("permission_id") REFERENCES "pool_schema"."permissions"("permission_id") ON DELETE CASCADE;


--
-- TOC entry 5288 (class 2606 OID 42707)
-- Name: role_permissions pool_schema_role_permissions_role_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."role_permissions"
    ADD CONSTRAINT "pool_schema_role_permissions_role_id_foreign" FOREIGN KEY ("role_id") REFERENCES "pool_schema"."roles"("role_id") ON DELETE CASCADE;


--
-- TOC entry 5316 (class 2606 OID 43049)
-- Name: sale_items pool_schema_sale_items_product_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."sale_items"
    ADD CONSTRAINT "pool_schema_sale_items_product_id_foreign" FOREIGN KEY ("product_id") REFERENCES "pool_schema"."products"("id") ON DELETE CASCADE;


--
-- TOC entry 5317 (class 2606 OID 43044)
-- Name: sale_items pool_schema_sale_items_sale_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."sale_items"
    ADD CONSTRAINT "pool_schema_sale_items_sale_id_foreign" FOREIGN KEY ("sale_id") REFERENCES "pool_schema"."sales"("id") ON DELETE CASCADE;


--
-- TOC entry 5314 (class 2606 OID 43026)
-- Name: sales pool_schema_sales_member_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."sales"
    ADD CONSTRAINT "pool_schema_sales_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "pool_schema"."members"("member_id") ON DELETE SET NULL;


--
-- TOC entry 5315 (class 2606 OID 43021)
-- Name: sales pool_schema_sales_staff_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."sales"
    ADD CONSTRAINT "pool_schema_sales_staff_id_foreign" FOREIGN KEY ("staff_id") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE CASCADE;


--
-- TOC entry 5319 (class 2606 OID 43091)
-- Name: staff_leaves pool_schema_staff_leaves_staff_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."staff_leaves"
    ADD CONSTRAINT "pool_schema_staff_leaves_staff_id_foreign" FOREIGN KEY ("staff_id") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE CASCADE;


--
-- TOC entry 5289 (class 2606 OID 42733)
-- Name: staff pool_schema_staff_role_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."staff"
    ADD CONSTRAINT "pool_schema_staff_role_id_foreign" FOREIGN KEY ("role_id") REFERENCES "pool_schema"."roles"("role_id") ON DELETE SET NULL;


--
-- TOC entry 5318 (class 2606 OID 43070)
-- Name: staff_schedules pool_schema_staff_schedules_staff_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."staff_schedules"
    ADD CONSTRAINT "pool_schema_staff_schedules_staff_id_foreign" FOREIGN KEY ("staff_id") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE CASCADE;


--
-- TOC entry 5309 (class 2606 OID 42944)
-- Name: subscription_allowed_days pool_schema_subscription_allowed_days_subscription_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscription_allowed_days"
    ADD CONSTRAINT "pool_schema_subscription_allowed_days_subscription_id_foreign" FOREIGN KEY ("subscription_id") REFERENCES "pool_schema"."subscriptions"("subscription_id") ON DELETE CASCADE;


--
-- TOC entry 5310 (class 2606 OID 42949)
-- Name: subscription_allowed_days pool_schema_subscription_allowed_days_weekday_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscription_allowed_days"
    ADD CONSTRAINT "pool_schema_subscription_allowed_days_weekday_id_foreign" FOREIGN KEY ("weekday_id") REFERENCES "pool_schema"."weekdays"("weekday_id") ON DELETE CASCADE;


--
-- TOC entry 5297 (class 2606 OID 42869)
-- Name: subscriptions pool_schema_subscriptions_activity_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscriptions"
    ADD CONSTRAINT "pool_schema_subscriptions_activity_id_foreign" FOREIGN KEY ("activity_id") REFERENCES "pool_schema"."activities"("activity_id") ON DELETE SET NULL;


--
-- TOC entry 5298 (class 2606 OID 42859)
-- Name: subscriptions pool_schema_subscriptions_created_by_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscriptions"
    ADD CONSTRAINT "pool_schema_subscriptions_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE SET NULL;


--
-- TOC entry 5299 (class 2606 OID 42854)
-- Name: subscriptions pool_schema_subscriptions_deactivated_by_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscriptions"
    ADD CONSTRAINT "pool_schema_subscriptions_deactivated_by_foreign" FOREIGN KEY ("deactivated_by") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE SET NULL;


--
-- TOC entry 5300 (class 2606 OID 42844)
-- Name: subscriptions pool_schema_subscriptions_member_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscriptions"
    ADD CONSTRAINT "pool_schema_subscriptions_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "pool_schema"."members"("member_id") ON DELETE CASCADE;


--
-- TOC entry 5301 (class 2606 OID 42849)
-- Name: subscriptions pool_schema_subscriptions_plan_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscriptions"
    ADD CONSTRAINT "pool_schema_subscriptions_plan_id_foreign" FOREIGN KEY ("plan_id") REFERENCES "pool_schema"."plans"("plan_id") ON DELETE CASCADE;


--
-- TOC entry 5302 (class 2606 OID 42864)
-- Name: subscriptions pool_schema_subscriptions_updated_by_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."subscriptions"
    ADD CONSTRAINT "pool_schema_subscriptions_updated_by_foreign" FOREIGN KEY ("updated_by") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE SET NULL;


--
-- TOC entry 5290 (class 2606 OID 42784)
-- Name: time_slots pool_schema_time_slots_activity_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."time_slots"
    ADD CONSTRAINT "pool_schema_time_slots_activity_id_foreign" FOREIGN KEY ("activity_id") REFERENCES "pool_schema"."activities"("activity_id") ON DELETE SET NULL;


--
-- TOC entry 5291 (class 2606 OID 43151)
-- Name: time_slots pool_schema_time_slots_assistant_coach_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."time_slots"
    ADD CONSTRAINT "pool_schema_time_slots_assistant_coach_id_foreign" FOREIGN KEY ("assistant_coach_id") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE SET NULL;


--
-- TOC entry 5292 (class 2606 OID 43117)
-- Name: time_slots pool_schema_time_slots_coach_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."time_slots"
    ADD CONSTRAINT "pool_schema_time_slots_coach_id_foreign" FOREIGN KEY ("coach_id") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE SET NULL;


--
-- TOC entry 5293 (class 2606 OID 42789)
-- Name: time_slots pool_schema_time_slots_created_by_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."time_slots"
    ADD CONSTRAINT "pool_schema_time_slots_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "pool_schema"."staff"("staff_id") ON DELETE SET NULL;


--
-- TOC entry 5294 (class 2606 OID 42779)
-- Name: time_slots pool_schema_time_slots_weekday_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."time_slots"
    ADD CONSTRAINT "pool_schema_time_slots_weekday_id_foreign" FOREIGN KEY ("weekday_id") REFERENCES "pool_schema"."weekdays"("weekday_id") ON DELETE CASCADE;


--
-- TOC entry 5321 (class 2606 OID 43142)
-- Name: product_images product_images_product_id_foreign; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY "pool_schema"."product_images"
    ADD CONSTRAINT "product_images_product_id_foreign" FOREIGN KEY ("product_id") REFERENCES "pool_schema"."products"("id") ON DELETE CASCADE;


--
-- TOC entry 5577 (class 0 OID 0)
-- Dependencies: 6
-- Name: SCHEMA "pool_schema"; Type: ACL; Schema: -; Owner: pooladmin
--

REVOKE ALL ON SCHEMA "pool_schema" FROM "pooladmin";
GRANT ALL ON SCHEMA "pool_schema" TO "pooladmin" WITH GRANT OPTION;


-- Completed on 2025-12-06 14:06:17

--
-- PostgreSQL database dump complete
--

\unrestrict NC6pqeuFox8EqK5KTi6DmycAueXDgKWgk4PbHZar8HfecfOWxrdBJahYH40V5VC


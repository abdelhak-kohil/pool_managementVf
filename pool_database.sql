--
-- PostgreSQL database dump
--

\restrict 5HPjhRiFy0QR4IYOi7CtnRxoqmIqX3R7Hm11063w6nsReXR9AUbhHRFiN61cxqc

-- Dumped from database version 18.0
-- Dumped by pg_dump version 18.0

-- Started on 2025-11-24 18:24:29

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
-- TOC entry 7 (class 2615 OID 16393)
-- Name: pool_schema; Type: SCHEMA; Schema: -; Owner: pooladmin
--

CREATE SCHEMA pool_schema;


ALTER SCHEMA pool_schema OWNER TO pooladmin;

--
-- TOC entry 970 (class 1247 OID 16432)
-- Name: access_decision_enum; Type: TYPE; Schema: pool_schema; Owner: pooladmin
--

CREATE TYPE pool_schema.access_decision_enum AS ENUM (
    'granted',
    'denied'
);


ALTER TYPE pool_schema.access_decision_enum OWNER TO pooladmin;

--
-- TOC entry 967 (class 1247 OID 16422)
-- Name: badge_status_enum; Type: TYPE; Schema: pool_schema; Owner: pooladmin
--

CREATE TYPE pool_schema.badge_status_enum AS ENUM (
    'active',
    'inactive',
    'lost',
    'revoked',
    'blocked'
);


ALTER TYPE pool_schema.badge_status_enum OWNER TO pooladmin;

--
-- TOC entry 964 (class 1247 OID 16414)
-- Name: facility_status_enum; Type: TYPE; Schema: pool_schema; Owner: pooladmin
--

CREATE TYPE pool_schema.facility_status_enum AS ENUM (
    'operational',
    'under_maintenance',
    'closed'
);


ALTER TYPE pool_schema.facility_status_enum OWNER TO pooladmin;

--
-- TOC entry 961 (class 1247 OID 16410)
-- Name: payment_method_enum; Type: TYPE; Schema: pool_schema; Owner: pooladmin
--

CREATE TYPE pool_schema.payment_method_enum AS ENUM (
    'cash',
    'card',
    'Virement',
    'transfer'
);


ALTER TYPE pool_schema.payment_method_enum OWNER TO pooladmin;

--
-- TOC entry 955 (class 1247 OID 16395)
-- Name: plan_type_enum; Type: TYPE; Schema: pool_schema; Owner: pooladmin
--

CREATE TYPE pool_schema.plan_type_enum AS ENUM (
    'monthly_weekly',
    'per_visit'
);


ALTER TYPE pool_schema.plan_type_enum OWNER TO pooladmin;

--
-- TOC entry 958 (class 1247 OID 16400)
-- Name: subscription_status_enum; Type: TYPE; Schema: pool_schema; Owner: pooladmin
--

CREATE TYPE pool_schema.subscription_status_enum AS ENUM (
    'active',
    'paused',
    'expired',
    'cancelled'
);


ALTER TYPE pool_schema.subscription_status_enum OWNER TO pooladmin;

--
-- TOC entry 333 (class 1255 OID 17106)
-- Name: fn_audit_log_changes(); Type: FUNCTION; Schema: pool_schema; Owner: postgres
--

CREATE FUNCTION pool_schema.fn_audit_log_changes() RETURNS trigger
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
    v_action TEXT;
    v_old_data JSONB;
    v_new_data JSONB;
    v_record_id TEXT;
BEGIN
    IF TG_OP = 'INSERT' THEN
        v_action := 'INSERT';
        v_old_data := NULL;
        v_new_data := to_jsonb(NEW);
        v_record_id := COALESCE(
            (to_jsonb(NEW)->>'member_id'),
            (to_jsonb(NEW)->>'subscription_id'),
            (to_jsonb(NEW)->>'badge_id'),
            (to_jsonb(NEW)->>'staff_id'),
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


ALTER FUNCTION pool_schema.fn_audit_log_changes() OWNER TO postgres;

--
-- TOC entry 332 (class 1255 OID 17093)
-- Name: fn_cleanup_old_audit_logs(integer); Type: FUNCTION; Schema: pool_schema; Owner: postgres
--

CREATE FUNCTION pool_schema.fn_cleanup_old_audit_logs(retention_days integer DEFAULT 2) RETURNS void
    LANGUAGE plpgsql SECURITY DEFINER
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


ALTER FUNCTION pool_schema.fn_cleanup_old_audit_logs(retention_days integer) OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 241 (class 1259 OID 16701)
-- Name: access_badges; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.access_badges (
    badge_id bigint NOT NULL,
    member_id bigint,
    badge_uid character varying(100) NOT NULL,
    status pool_schema.badge_status_enum DEFAULT 'active'::pool_schema.badge_status_enum NOT NULL,
    issued_at timestamp with time zone DEFAULT now() NOT NULL,
    expires_at timestamp with time zone
);


ALTER TABLE pool_schema.access_badges OWNER TO pooladmin;

--
-- TOC entry 240 (class 1259 OID 16700)
-- Name: access_badges_badge_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.access_badges_badge_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.access_badges_badge_id_seq OWNER TO pooladmin;

--
-- TOC entry 5466 (class 0 OID 0)
-- Dependencies: 240
-- Name: access_badges_badge_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.access_badges_badge_id_seq OWNED BY pool_schema.access_badges.badge_id;


--
-- TOC entry 243 (class 1259 OID 16724)
-- Name: access_logs; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.access_logs (
    log_id bigint NOT NULL,
    badge_uid character varying(100) NOT NULL,
    member_id bigint,
    access_time timestamp with time zone DEFAULT now() NOT NULL,
    access_decision pool_schema.access_decision_enum NOT NULL,
    denial_reason text
);


ALTER TABLE pool_schema.access_logs OWNER TO pooladmin;

--
-- TOC entry 242 (class 1259 OID 16723)
-- Name: access_logs_log_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.access_logs_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.access_logs_log_id_seq OWNER TO pooladmin;

--
-- TOC entry 5467 (class 0 OID 0)
-- Dependencies: 242
-- Name: access_logs_log_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.access_logs_log_id_seq OWNED BY pool_schema.access_logs.log_id;


--
-- TOC entry 275 (class 1259 OID 17154)
-- Name: activities; Type: TABLE; Schema: pool_schema; Owner: postgres
--

CREATE TABLE pool_schema.activities (
    activity_id bigint NOT NULL,
    name character varying(100) NOT NULL,
    description text,
    access_type character varying(50),
    color_code character varying(20) DEFAULT '#60a5fa'::character varying,
    is_active boolean DEFAULT true NOT NULL
);


ALTER TABLE pool_schema.activities OWNER TO postgres;

--
-- TOC entry 274 (class 1259 OID 17153)
-- Name: activities_activity_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: postgres
--

CREATE SEQUENCE pool_schema.activities_activity_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.activities_activity_id_seq OWNER TO postgres;

--
-- TOC entry 5468 (class 0 OID 0)
-- Dependencies: 274
-- Name: activities_activity_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: postgres
--

ALTER SEQUENCE pool_schema.activities_activity_id_seq OWNED BY pool_schema.activities.activity_id;


--
-- TOC entry 283 (class 1259 OID 17240)
-- Name: activity_plan_prices; Type: TABLE; Schema: pool_schema; Owner: postgres
--

CREATE TABLE pool_schema.activity_plan_prices (
    id bigint NOT NULL,
    activity_id bigint NOT NULL,
    plan_id bigint NOT NULL,
    price numeric(10,2) NOT NULL,
    CONSTRAINT activity_plan_prices_price_check CHECK ((price >= (0)::numeric))
);


ALTER TABLE pool_schema.activity_plan_prices OWNER TO postgres;

--
-- TOC entry 5469 (class 0 OID 0)
-- Dependencies: 283
-- Name: TABLE activity_plan_prices; Type: COMMENT; Schema: pool_schema; Owner: postgres
--

COMMENT ON TABLE pool_schema.activity_plan_prices IS 'Defines specific pricing rules between activities and plans (per activity-plan pair).';


--
-- TOC entry 5470 (class 0 OID 0)
-- Dependencies: 283
-- Name: COLUMN activity_plan_prices.price; Type: COMMENT; Schema: pool_schema; Owner: postgres
--

COMMENT ON COLUMN pool_schema.activity_plan_prices.price IS 'Custom price for this combination of activity and plan.';


--
-- TOC entry 282 (class 1259 OID 17239)
-- Name: activity_plan_prices_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: postgres
--

CREATE SEQUENCE pool_schema.activity_plan_prices_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.activity_plan_prices_id_seq OWNER TO postgres;

--
-- TOC entry 5471 (class 0 OID 0)
-- Dependencies: 282
-- Name: activity_plan_prices_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: postgres
--

ALTER SEQUENCE pool_schema.activity_plan_prices_id_seq OWNED BY pool_schema.activity_plan_prices.id;


--
-- TOC entry 245 (class 1259 OID 16750)
-- Name: audit_log; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.audit_log (
    log_id bigint NOT NULL,
    table_name text NOT NULL,
    record_id text,
    action text NOT NULL,
    changed_by_staff_id bigint,
    change_timestamp timestamp with time zone DEFAULT now() NOT NULL,
    old_data_jsonb jsonb,
    new_data_jsonb jsonb
);


ALTER TABLE pool_schema.audit_log OWNER TO pooladmin;

--
-- TOC entry 244 (class 1259 OID 16749)
-- Name: audit_log_log_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.audit_log_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.audit_log_log_id_seq OWNER TO pooladmin;

--
-- TOC entry 5472 (class 0 OID 0)
-- Dependencies: 244
-- Name: audit_log_log_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.audit_log_log_id_seq OWNED BY pool_schema.audit_log.log_id;


--
-- TOC entry 273 (class 1259 OID 17095)
-- Name: audit_settings; Type: TABLE; Schema: pool_schema; Owner: postgres
--

CREATE TABLE pool_schema.audit_settings (
    id bigint NOT NULL,
    retention_days integer DEFAULT 90 NOT NULL,
    last_manual_run_at timestamp with time zone,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE pool_schema.audit_settings OWNER TO postgres;

--
-- TOC entry 272 (class 1259 OID 17094)
-- Name: audit_settings_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: postgres
--

CREATE SEQUENCE pool_schema.audit_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.audit_settings_id_seq OWNER TO postgres;

--
-- TOC entry 5473 (class 0 OID 0)
-- Dependencies: 272
-- Name: audit_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: postgres
--

ALTER SEQUENCE pool_schema.audit_settings_id_seq OWNED BY pool_schema.audit_settings.id;


--
-- TOC entry 265 (class 1259 OID 16970)
-- Name: cache; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE pool_schema.cache OWNER TO pooladmin;

--
-- TOC entry 266 (class 1259 OID 16980)
-- Name: cache_locks; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE pool_schema.cache_locks OWNER TO pooladmin;

--
-- TOC entry 239 (class 1259 OID 16687)
-- Name: facilities; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.facilities (
    facility_id bigint NOT NULL,
    name character varying(100) NOT NULL,
    capacity integer,
    status pool_schema.facility_status_enum DEFAULT 'operational'::pool_schema.facility_status_enum NOT NULL,
    CONSTRAINT capacity_check CHECK ((capacity >= 0))
);


ALTER TABLE pool_schema.facilities OWNER TO pooladmin;

--
-- TOC entry 238 (class 1259 OID 16686)
-- Name: facilities_facility_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.facilities_facility_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.facilities_facility_id_seq OWNER TO pooladmin;

--
-- TOC entry 5474 (class 0 OID 0)
-- Dependencies: 238
-- Name: facilities_facility_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.facilities_facility_id_seq OWNED BY pool_schema.facilities.facility_id;


--
-- TOC entry 271 (class 1259 OID 17021)
-- Name: failed_jobs; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE pool_schema.failed_jobs OWNER TO pooladmin;

--
-- TOC entry 270 (class 1259 OID 17020)
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.failed_jobs_id_seq OWNER TO pooladmin;

--
-- TOC entry 5475 (class 0 OID 0)
-- Dependencies: 270
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.failed_jobs_id_seq OWNED BY pool_schema.failed_jobs.id;


--
-- TOC entry 269 (class 1259 OID 17006)
-- Name: job_batches; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE pool_schema.job_batches OWNER TO pooladmin;

--
-- TOC entry 268 (class 1259 OID 16991)
-- Name: jobs; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE pool_schema.jobs OWNER TO pooladmin;

--
-- TOC entry 267 (class 1259 OID 16990)
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.jobs_id_seq OWNER TO pooladmin;

--
-- TOC entry 5476 (class 0 OID 0)
-- Dependencies: 267
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.jobs_id_seq OWNED BY pool_schema.jobs.id;


--
-- TOC entry 222 (class 1259 OID 16485)
-- Name: members; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.members (
    member_id bigint NOT NULL,
    first_name character varying(100) NOT NULL,
    last_name character varying(100) NOT NULL,
    email character varying(255),
    phone_number character varying(20),
    date_of_birth date,
    address text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    created_by bigint,
    updated_by bigint
);


ALTER TABLE pool_schema.members OWNER TO pooladmin;

--
-- TOC entry 221 (class 1259 OID 16484)
-- Name: members_member_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.members_member_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.members_member_id_seq OWNER TO pooladmin;

--
-- TOC entry 5477 (class 0 OID 0)
-- Dependencies: 221
-- Name: members_member_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.members_member_id_seq OWNED BY pool_schema.members.member_id;


--
-- TOC entry 260 (class 1259 OID 16925)
-- Name: migrations; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE pool_schema.migrations OWNER TO pooladmin;

--
-- TOC entry 259 (class 1259 OID 16924)
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.migrations_id_seq OWNER TO pooladmin;

--
-- TOC entry 5478 (class 0 OID 0)
-- Dependencies: 259
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.migrations_id_seq OWNED BY pool_schema.migrations.id;


--
-- TOC entry 279 (class 1259 OID 17196)
-- Name: partner_groups; Type: TABLE; Schema: pool_schema; Owner: postgres
--

CREATE TABLE pool_schema.partner_groups (
    group_id bigint NOT NULL,
    name character varying(150) NOT NULL,
    contact_name character varying(100),
    contact_phone character varying(30),
    email character varying(100),
    notes text
);


ALTER TABLE pool_schema.partner_groups OWNER TO postgres;

--
-- TOC entry 278 (class 1259 OID 17195)
-- Name: partner_groups_group_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: postgres
--

CREATE SEQUENCE pool_schema.partner_groups_group_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.partner_groups_group_id_seq OWNER TO postgres;

--
-- TOC entry 5479 (class 0 OID 0)
-- Dependencies: 278
-- Name: partner_groups_group_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: postgres
--

ALTER SEQUENCE pool_schema.partner_groups_group_id_seq OWNED BY pool_schema.partner_groups.group_id;


--
-- TOC entry 263 (class 1259 OID 16949)
-- Name: password_reset_tokens; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE pool_schema.password_reset_tokens OWNER TO pooladmin;

--
-- TOC entry 237 (class 1259 OID 16659)
-- Name: payments; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.payments (
    payment_id bigint NOT NULL,
    subscription_id bigint NOT NULL,
    amount numeric(10,2) NOT NULL,
    payment_date timestamp with time zone DEFAULT now() NOT NULL,
    payment_method pool_schema.payment_method_enum DEFAULT 'cash'::pool_schema.payment_method_enum NOT NULL,
    received_by_staff_id bigint NOT NULL,
    notes text,
    CONSTRAINT amount_check CHECK ((amount >= (0)::numeric))
);


ALTER TABLE pool_schema.payments OWNER TO pooladmin;

--
-- TOC entry 236 (class 1259 OID 16658)
-- Name: payments_payment_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.payments_payment_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.payments_payment_id_seq OWNER TO pooladmin;

--
-- TOC entry 5480 (class 0 OID 0)
-- Dependencies: 236
-- Name: payments_payment_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.payments_payment_id_seq OWNED BY pool_schema.payments.payment_id;


--
-- TOC entry 226 (class 1259 OID 16514)
-- Name: permissions; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.permissions (
    permission_id bigint NOT NULL,
    permission_name character varying(100) NOT NULL
);


ALTER TABLE pool_schema.permissions OWNER TO pooladmin;

--
-- TOC entry 225 (class 1259 OID 16513)
-- Name: permissions_permission_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.permissions_permission_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.permissions_permission_id_seq OWNER TO pooladmin;

--
-- TOC entry 5481 (class 0 OID 0)
-- Dependencies: 225
-- Name: permissions_permission_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.permissions_permission_id_seq OWNED BY pool_schema.permissions.permission_id;


--
-- TOC entry 231 (class 1259 OID 16589)
-- Name: plans; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.plans (
    plan_id bigint NOT NULL,
    plan_name character varying(255) NOT NULL,
    description text,
    price numeric(10,2) NOT NULL,
    plan_type pool_schema.plan_type_enum NOT NULL,
    visits_per_week smallint,
    duration_months smallint,
    is_active boolean DEFAULT true NOT NULL,
    CONSTRAINT plan_logic_check CHECK ((((plan_type = 'per_visit'::pool_schema.plan_type_enum) AND (visits_per_week IS NULL) AND (duration_months IS NULL)) OR ((plan_type = 'monthly_weekly'::pool_schema.plan_type_enum) AND (visits_per_week IS NOT NULL) AND (duration_months IS NOT NULL)))),
    CONSTRAINT price_check CHECK ((price >= (0)::numeric))
);


ALTER TABLE pool_schema.plans OWNER TO pooladmin;

--
-- TOC entry 230 (class 1259 OID 16588)
-- Name: plans_plan_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.plans_plan_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.plans_plan_id_seq OWNER TO pooladmin;

--
-- TOC entry 5482 (class 0 OID 0)
-- Dependencies: 230
-- Name: plans_plan_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.plans_plan_id_seq OWNED BY pool_schema.plans.plan_id;


--
-- TOC entry 281 (class 1259 OID 17209)
-- Name: reservations; Type: TABLE; Schema: pool_schema; Owner: postgres
--

CREATE TABLE pool_schema.reservations (
    reservation_id bigint NOT NULL,
    slot_id bigint NOT NULL,
    member_id bigint,
    partner_group_id bigint,
    reservation_type character varying(20) NOT NULL,
    reserved_at timestamp with time zone DEFAULT now(),
    status character varying(20) DEFAULT 'confirmed'::character varying,
    notes text,
    CONSTRAINT reservations_reservation_type_check CHECK (((reservation_type)::text = ANY ((ARRAY['member_private'::character varying, 'partner_group'::character varying])::text[]))),
    CONSTRAINT reservations_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'confirmed'::character varying, 'cancelled'::character varying])::text[])))
);


ALTER TABLE pool_schema.reservations OWNER TO postgres;

--
-- TOC entry 280 (class 1259 OID 17208)
-- Name: reservations_reservation_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: postgres
--

CREATE SEQUENCE pool_schema.reservations_reservation_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.reservations_reservation_id_seq OWNER TO postgres;

--
-- TOC entry 5483 (class 0 OID 0)
-- Dependencies: 280
-- Name: reservations_reservation_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: postgres
--

ALTER SEQUENCE pool_schema.reservations_reservation_id_seq OWNED BY pool_schema.reservations.reservation_id;


--
-- TOC entry 227 (class 1259 OID 16524)
-- Name: role_permissions; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.role_permissions (
    role_id bigint NOT NULL,
    permission_id bigint NOT NULL
);


ALTER TABLE pool_schema.role_permissions OWNER TO pooladmin;

--
-- TOC entry 224 (class 1259 OID 16503)
-- Name: roles; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.roles (
    role_id bigint NOT NULL,
    role_name character varying(50) NOT NULL
);


ALTER TABLE pool_schema.roles OWNER TO pooladmin;

--
-- TOC entry 223 (class 1259 OID 16502)
-- Name: roles_role_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.roles_role_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.roles_role_id_seq OWNER TO pooladmin;

--
-- TOC entry 5484 (class 0 OID 0)
-- Dependencies: 223
-- Name: roles_role_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.roles_role_id_seq OWNED BY pool_schema.roles.role_id;


--
-- TOC entry 264 (class 1259 OID 16958)
-- Name: sessions; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE pool_schema.sessions OWNER TO pooladmin;

--
-- TOC entry 229 (class 1259 OID 16563)
-- Name: staff; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.staff (
    staff_id bigint NOT NULL,
    first_name character varying(100) NOT NULL,
    last_name character varying(100) NOT NULL,
    username character varying(50) NOT NULL,
    password_hash character varying(255) NOT NULL,
    role_id bigint NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE pool_schema.staff OWNER TO pooladmin;

--
-- TOC entry 228 (class 1259 OID 16562)
-- Name: staff_staff_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.staff_staff_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.staff_staff_id_seq OWNER TO pooladmin;

--
-- TOC entry 5485 (class 0 OID 0)
-- Dependencies: 228
-- Name: staff_staff_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.staff_staff_id_seq OWNED BY pool_schema.staff.staff_id;


--
-- TOC entry 235 (class 1259 OID 16641)
-- Name: subscription_allowed_days; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.subscription_allowed_days (
    subscription_id bigint NOT NULL,
    weekday_id smallint NOT NULL
);


ALTER TABLE pool_schema.subscription_allowed_days OWNER TO pooladmin;

--
-- TOC entry 233 (class 1259 OID 16606)
-- Name: subscriptions; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.subscriptions (
    subscription_id bigint NOT NULL,
    member_id bigint NOT NULL,
    plan_id bigint NOT NULL,
    start_date date NOT NULL,
    end_date date NOT NULL,
    status pool_schema.subscription_status_enum DEFAULT 'active'::pool_schema.subscription_status_enum NOT NULL,
    paused_at timestamp with time zone,
    resumes_at date,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    visits_per_week smallint,
    deactivated_by bigint,
    created_by bigint,
    updated_by bigint,
    updated_at timestamp with time zone DEFAULT now(),
    activity_id bigint,
    CONSTRAINT dates_check CHECK ((end_date >= start_date)),
    CONSTRAINT subscriptions_visits_per_week_check CHECK (((visits_per_week >= 1) AND (visits_per_week <= 7)))
);


ALTER TABLE pool_schema.subscriptions OWNER TO pooladmin;

--
-- TOC entry 232 (class 1259 OID 16605)
-- Name: subscriptions_subscription_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.subscriptions_subscription_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.subscriptions_subscription_id_seq OWNER TO pooladmin;

--
-- TOC entry 5486 (class 0 OID 0)
-- Dependencies: 232
-- Name: subscriptions_subscription_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.subscriptions_subscription_id_seq OWNED BY pool_schema.subscriptions.subscription_id;


--
-- TOC entry 277 (class 1259 OID 17170)
-- Name: time_slots; Type: TABLE; Schema: pool_schema; Owner: postgres
--

CREATE TABLE pool_schema.time_slots (
    slot_id bigint NOT NULL,
    weekday_id smallint NOT NULL,
    start_time time without time zone NOT NULL,
    end_time time without time zone NOT NULL,
    activity_id bigint,
    assigned_group character varying(200),
    is_blocked boolean DEFAULT false NOT NULL,
    notes text,
    created_by integer,
    updated_at timestamp with time zone DEFAULT now(),
    created_at timestamp with time zone DEFAULT now(),
    CONSTRAINT chk_time_valid CHECK ((start_time < end_time))
);


ALTER TABLE pool_schema.time_slots OWNER TO postgres;

--
-- TOC entry 276 (class 1259 OID 17169)
-- Name: time_slots_slot_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: postgres
--

CREATE SEQUENCE pool_schema.time_slots_slot_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.time_slots_slot_id_seq OWNER TO postgres;

--
-- TOC entry 5487 (class 0 OID 0)
-- Dependencies: 276
-- Name: time_slots_slot_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: postgres
--

ALTER SEQUENCE pool_schema.time_slots_slot_id_seq OWNED BY pool_schema.time_slots.slot_id;


--
-- TOC entry 262 (class 1259 OID 16935)
-- Name: users; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE pool_schema.users OWNER TO pooladmin;

--
-- TOC entry 261 (class 1259 OID 16934)
-- Name: users_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.users_id_seq OWNER TO pooladmin;

--
-- TOC entry 5488 (class 0 OID 0)
-- Dependencies: 261
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.users_id_seq OWNED BY pool_schema.users.id;


--
-- TOC entry 234 (class 1259 OID 16632)
-- Name: weekdays; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.weekdays (
    weekday_id smallint NOT NULL,
    day_name character varying(10) NOT NULL
);


ALTER TABLE pool_schema.weekdays OWNER TO pooladmin;

--
-- TOC entry 5097 (class 2604 OID 16704)
-- Name: access_badges badge_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.access_badges ALTER COLUMN badge_id SET DEFAULT nextval('pool_schema.access_badges_badge_id_seq'::regclass);


--
-- TOC entry 5100 (class 2604 OID 16727)
-- Name: access_logs log_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.access_logs ALTER COLUMN log_id SET DEFAULT nextval('pool_schema.access_logs_log_id_seq'::regclass);


--
-- TOC entry 5112 (class 2604 OID 17157)
-- Name: activities activity_id; Type: DEFAULT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.activities ALTER COLUMN activity_id SET DEFAULT nextval('pool_schema.activities_activity_id_seq'::regclass);


--
-- TOC entry 5123 (class 2604 OID 17243)
-- Name: activity_plan_prices id; Type: DEFAULT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.activity_plan_prices ALTER COLUMN id SET DEFAULT nextval('pool_schema.activity_plan_prices_id_seq'::regclass);


--
-- TOC entry 5102 (class 2604 OID 16753)
-- Name: audit_log log_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.audit_log ALTER COLUMN log_id SET DEFAULT nextval('pool_schema.audit_log_log_id_seq'::regclass);


--
-- TOC entry 5109 (class 2604 OID 17098)
-- Name: audit_settings id; Type: DEFAULT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.audit_settings ALTER COLUMN id SET DEFAULT nextval('pool_schema.audit_settings_id_seq'::regclass);


--
-- TOC entry 5095 (class 2604 OID 16690)
-- Name: facilities facility_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.facilities ALTER COLUMN facility_id SET DEFAULT nextval('pool_schema.facilities_facility_id_seq'::regclass);


--
-- TOC entry 5107 (class 2604 OID 17024)
-- Name: failed_jobs id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.failed_jobs ALTER COLUMN id SET DEFAULT nextval('pool_schema.failed_jobs_id_seq'::regclass);


--
-- TOC entry 5106 (class 2604 OID 16994)
-- Name: jobs id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.jobs ALTER COLUMN id SET DEFAULT nextval('pool_schema.jobs_id_seq'::regclass);


--
-- TOC entry 5078 (class 2604 OID 16488)
-- Name: members member_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.members ALTER COLUMN member_id SET DEFAULT nextval('pool_schema.members_member_id_seq'::regclass);


--
-- TOC entry 5104 (class 2604 OID 16928)
-- Name: migrations id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.migrations ALTER COLUMN id SET DEFAULT nextval('pool_schema.migrations_id_seq'::regclass);


--
-- TOC entry 5119 (class 2604 OID 17199)
-- Name: partner_groups group_id; Type: DEFAULT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.partner_groups ALTER COLUMN group_id SET DEFAULT nextval('pool_schema.partner_groups_group_id_seq'::regclass);


--
-- TOC entry 5092 (class 2604 OID 16662)
-- Name: payments payment_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.payments ALTER COLUMN payment_id SET DEFAULT nextval('pool_schema.payments_payment_id_seq'::regclass);


--
-- TOC entry 5082 (class 2604 OID 16517)
-- Name: permissions permission_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.permissions ALTER COLUMN permission_id SET DEFAULT nextval('pool_schema.permissions_permission_id_seq'::regclass);


--
-- TOC entry 5086 (class 2604 OID 16592)
-- Name: plans plan_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.plans ALTER COLUMN plan_id SET DEFAULT nextval('pool_schema.plans_plan_id_seq'::regclass);


--
-- TOC entry 5120 (class 2604 OID 17212)
-- Name: reservations reservation_id; Type: DEFAULT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.reservations ALTER COLUMN reservation_id SET DEFAULT nextval('pool_schema.reservations_reservation_id_seq'::regclass);


--
-- TOC entry 5081 (class 2604 OID 16506)
-- Name: roles role_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.roles ALTER COLUMN role_id SET DEFAULT nextval('pool_schema.roles_role_id_seq'::regclass);


--
-- TOC entry 5083 (class 2604 OID 16566)
-- Name: staff staff_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.staff ALTER COLUMN staff_id SET DEFAULT nextval('pool_schema.staff_staff_id_seq'::regclass);


--
-- TOC entry 5088 (class 2604 OID 16609)
-- Name: subscriptions subscription_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscriptions ALTER COLUMN subscription_id SET DEFAULT nextval('pool_schema.subscriptions_subscription_id_seq'::regclass);


--
-- TOC entry 5115 (class 2604 OID 17173)
-- Name: time_slots slot_id; Type: DEFAULT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.time_slots ALTER COLUMN slot_id SET DEFAULT nextval('pool_schema.time_slots_slot_id_seq'::regclass);


--
-- TOC entry 5105 (class 2604 OID 16938)
-- Name: users id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.users ALTER COLUMN id SET DEFAULT nextval('pool_schema.users_id_seq'::regclass);


--
-- TOC entry 5430 (class 0 OID 16701)
-- Dependencies: 241
-- Data for Name: access_badges; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO pool_schema.access_badges VALUES (27, 7, 'UID-3005', 'active', '2025-10-28 00:45:42+01', NULL);
INSERT INTO pool_schema.access_badges VALUES (4, 8, 'B-68F94CC8AF409', 'active', '2025-10-22 21:29:44+01', NULL);
INSERT INTO pool_schema.access_badges VALUES (5, 9, 'B-68F9651092F50', 'active', '2025-10-22 23:13:20+01', NULL);
INSERT INTO pool_schema.access_badges VALUES (9, 13, 'B-68F96B4B05AE3', 'active', '2025-10-22 23:39:55+01', NULL);
INSERT INTO pool_schema.access_badges VALUES (22, NULL, 'UID-2005', 'inactive', '2025-10-24 23:39:34.854398+01', NULL);
INSERT INTO pool_schema.access_badges VALUES (24, NULL, 'UID-3002', 'inactive', '2025-10-24 23:39:34.854398+01', NULL);
INSERT INTO pool_schema.access_badges VALUES (25, NULL, 'UID-3003', 'inactive', '2025-10-24 23:39:34.854398+01', NULL);
INSERT INTO pool_schema.access_badges VALUES (26, NULL, 'UID-3004', 'inactive', '2025-10-24 23:39:34.854398+01', NULL);
INSERT INTO pool_schema.access_badges VALUES (28, NULL, 'UID-3006', 'inactive', '2025-10-24 23:39:34.854398+01', NULL);
INSERT INTO pool_schema.access_badges VALUES (32, 18, 'UID-4001', 'active', '2025-10-24 23:39:34.854398+01', NULL);
INSERT INTO pool_schema.access_badges VALUES (13, 3, 'UID-1001', 'active', '2025-10-25 11:18:33+01', NULL);
INSERT INTO pool_schema.access_badges VALUES (17, 2, 'UID-1005', 'active', '2025-10-24 23:39:34.854398+01', NULL);
INSERT INTO pool_schema.access_badges VALUES (37, 1, 'UID-4006', 'active', '2025-10-25 22:06:19+01', NULL);
INSERT INTO pool_schema.access_badges VALUES (10, 21, 'B-68FA45C356A1B', 'active', '2025-10-23 15:12:03+01', NULL);
INSERT INTO pool_schema.access_badges VALUES (36, 23, 'UID-4005', 'active', '2025-10-24 23:39:34.854398+01', NULL);
INSERT INTO pool_schema.access_badges VALUES (19, 12, 'UID-2002', 'active', '2025-10-28 00:44:45+01', NULL);
INSERT INTO pool_schema.access_badges VALUES (20, 5, 'UID-2003', 'active', '2025-10-28 00:45:17+01', NULL);
INSERT INTO pool_schema.access_badges VALUES (39, 6, 'UID-50001', 'active', '2025-10-28 00:45:30+01', '2026-01-26 00:00:00+01');


--
-- TOC entry 5432 (class 0 OID 16724)
-- Dependencies: 243
-- Data for Name: access_logs; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO pool_schema.access_logs VALUES (1, '012547', 3, '2025-10-22 15:02:01.938018+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (2, '01225544', 2, '2025-10-22 15:02:16.36852+01', 'denied', NULL);
INSERT INTO pool_schema.access_logs VALUES (3, 'UNKNOWN', 3, '2025-10-22 19:22:56+01', 'denied', 'Inactive or missing badge');
INSERT INTO pool_schema.access_logs VALUES (4, 'UNKNOWN', 2, '2025-10-22 19:23:02+01', 'denied', 'Inactive or missing badge');
INSERT INTO pool_schema.access_logs VALUES (7, 'UNKNOWN', 1, '2025-10-22 19:23:26+01', 'denied', 'Inactive or missing badge');
INSERT INTO pool_schema.access_logs VALUES (8, 'UNKNOWN', 2, '2025-10-22 19:23:30+01', 'denied', 'Inactive or missing badge');
INSERT INTO pool_schema.access_logs VALUES (9, 'UNKNOWN', 3, '2025-10-22 19:23:35+01', 'denied', 'Inactive or missing badge');
INSERT INTO pool_schema.access_logs VALUES (10, 'UNKNOWN', 2, '2025-10-22 19:24:11+01', 'denied', 'Inactive or missing badge');
INSERT INTO pool_schema.access_logs VALUES (11, 'UNKNOWN', 1, '2025-10-22 19:24:18+01', 'denied', 'Inactive or missing badge');
INSERT INTO pool_schema.access_logs VALUES (12, 'B-68F92FCC766BC', 5, '2025-10-22 19:26:18+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (13, 'B-68F92FCC766BC', 5, '2025-10-22 19:27:30+01', 'denied', 'No active subscription');
INSERT INTO pool_schema.access_logs VALUES (14, 'B-68F92FCC766BC', 5, '2025-10-22 19:28:52+01', 'denied', 'Inactive or missing badge');
INSERT INTO pool_schema.access_logs VALUES (15, 'B-68F92FCC766BC', 5, '2025-10-22 19:28:57+01', 'denied', 'Inactive or missing badge');
INSERT INTO pool_schema.access_logs VALUES (16, 'B-68F92FCC766BC', 5, '2025-10-22 19:29:02+01', 'denied', 'Inactive or missing badge');
INSERT INTO pool_schema.access_logs VALUES (17, 'B-68F92FCC766BC', 5, '2025-10-22 19:29:05+01', 'denied', 'Inactive or missing badge');
INSERT INTO pool_schema.access_logs VALUES (18, 'B-68F92FCC766BC', 5, '2025-10-22 19:29:11+01', 'denied', 'No active subscription');
INSERT INTO pool_schema.access_logs VALUES (19, 'B-68F92FCC766BC', 5, '2025-10-22 19:29:12+01', 'denied', 'No active subscription');
INSERT INTO pool_schema.access_logs VALUES (20, 'UNKNOWN', 3, '2025-10-22 19:42:38+01', 'denied', 'Inactive or missing badge');
INSERT INTO pool_schema.access_logs VALUES (21, 'UNKNOWN', 3, '2025-10-22 20:05:34+01', 'denied', 'Inactive or missing badge');
INSERT INTO pool_schema.access_logs VALUES (22, 'UNKNOWN', 1, '2025-10-22 20:05:45+01', 'denied', 'Inactive or missing badge');
INSERT INTO pool_schema.access_logs VALUES (23, 'B-68F93720ACF83', 6, '2025-10-22 20:05:48+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (24, 'B-68F93720ACF83', 6, '2025-10-22 20:05:55+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (25, 'B-68F93720ACF83', 6, '2025-10-22 20:14:09+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (27, 'B-68F93BBC8B4E9', 7, '2025-10-22 20:26:22+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (28, 'UNKNOWN', 3, '2025-10-22 20:31:30+01', 'denied', 'Inactive or missing badge');
INSERT INTO pool_schema.access_logs VALUES (30, 'B-68F93720ACF83', 6, '2025-10-22 20:31:38+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (32, 'B-68F93720ACF83', 6, '2025-10-22 20:32:58+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (33, 'B-68F93BBC8B4E9', 7, '2025-10-22 20:33:04+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (34, 'B-68F92FCC766BC', 5, '2025-10-22 20:33:08+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (35, 'UNKNOWN', 3, '2025-10-22 20:33:13+01', 'denied', 'Inactive or missing badge');
INSERT INTO pool_schema.access_logs VALUES (36, 'UNKNOWN', 2, '2025-10-22 20:33:15+01', 'denied', 'Inactive or missing badge');
INSERT INTO pool_schema.access_logs VALUES (37, 'UNKNOWN', 1, '2025-10-22 20:33:18+01', 'denied', 'Inactive or missing badge');
INSERT INTO pool_schema.access_logs VALUES (38, 'B-68F93720ACF83', 6, '2025-10-22 20:33:20+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (39, 'UNKNOWN', 2, '2025-10-23 00:09:36+01', 'denied', 'Inactive or missing badge');
INSERT INTO pool_schema.access_logs VALUES (40, 'B-68F9651092F50', 9, '2025-10-23 00:09:49+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (41, 'UNKNOWN', 3, '2025-10-23 00:26:55+01', 'denied', 'Inactive or missing badge');
INSERT INTO pool_schema.access_logs VALUES (42, 'B-68F94CC8AF409', 8, '2025-10-23 00:27:42+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (67, 'B-68F92FCC766BC', 5, '2025-10-23 10:37:39+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (69, 'B-68F92FCC766BC', 5, '2025-10-23 10:37:57+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (70, 'B-68F92FCC766BC', 5, '2025-10-23 10:39:51+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (71, 'B-68F96967BBEE1', 12, '2025-10-23 10:51:09+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (74, 'B-68F96B4B05AE3', 13, '2025-10-23 10:51:25+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (76, 'B-68F96B4B05AE3', 13, '2025-10-23 10:54:01+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (77, 'B-68F94CC8AF409', 8, '2025-10-23 10:54:13+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (80, 'B-68F92FCC766BC', 5, '2025-10-23 10:58:33+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (81, 'UNKNOWN', 3, '2025-10-23 10:58:36+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (82, 'UNKNOWN', 2, '2025-10-23 10:58:40+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (83, 'UNKNOWN', 3, '2025-10-23 10:59:12+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (84, 'B-68F96B4B05AE3', 13, '2025-10-23 11:13:16+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (85, 'B-68F96B4B05AE3', 13, '2025-10-23 11:13:46+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (86, 'B-68F96B4B05AE3', 13, '2025-10-23 11:13:53+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (87, 'B-68F96B4B05AE3', 13, '2025-10-23 11:13:57+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (88, 'B-68F96B4B05AE3', 13, '2025-10-23 11:14:00+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (89, 'B-68F9651092F50', 9, '2025-10-23 11:14:03+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (90, 'B-68F9651092F50', 9, '2025-10-23 11:16:18+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (91, 'B-68F93BBC8B4E9', 7, '2025-10-23 11:18:28+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (92, 'B-68F96967BBEE1', 12, '2025-10-23 11:19:32+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (93, 'B-68F96B4B05AE3', 13, '2025-10-23 11:20:04+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (94, 'B-68F9651092F50', 9, '2025-10-23 11:29:53+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (95, 'B-68F93720ACF83', 6, '2025-10-23 11:30:02+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (96, 'UNKNOWN', 2, '2025-10-23 11:30:05+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (97, 'B-68F96B4B05AE3', 13, '2025-10-23 11:31:25+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (98, 'B-68F9651092F50', 9, '2025-10-23 11:31:28+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (99, 'B-68F93720ACF83', 6, '2025-10-23 11:31:31+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (100, 'UNKNOWN', 2, '2025-10-23 11:31:37+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (101, 'B-68F96967BBEE1', 12, '2025-10-23 11:36:29+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (102, 'B-68F94CC8AF409', 8, '2025-10-23 11:36:35+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (103, 'UNKNOWN', 3, '2025-10-23 11:36:54+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (104, 'UNKNOWN', 3, '2025-10-23 11:36:58+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (105, 'UNKNOWN', 3, '2025-10-23 11:40:24+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (106, 'B-68F96967BBEE1', 12, '2025-10-23 11:41:10+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (107, 'B-68F96B4B05AE3', 13, '2025-10-23 11:52:09+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (108, 'B-68F9651092F50', 9, '2025-10-23 11:52:11+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (109, 'B-68F96B4B05AE3', 13, '2025-10-23 11:58:32+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (110, 'B-68F96B4B05AE3', 13, '2025-10-23 12:05:53+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (111, 'B-68F96967BBEE1', 12, '2025-10-23 12:12:33+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (112, 'B-68F96B4B05AE3', 13, '2025-10-23 12:29:30+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (113, 'UNKNOWN', 3, '2025-10-23 12:33:31+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (114, 'B-68F92FCC766BC', 5, '2025-10-23 12:35:10+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (115, 'B-68F92FCC766BC', 5, '2025-10-23 12:35:12+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (118, 'UNKNOWN', 2, '2025-10-23 12:44:39+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (119, 'B-68F92FCC766BC', 5, '2025-10-23 12:48:56+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (120, 'B-68F93720ACF83', 6, '2025-10-23 12:48:58+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (121, 'UNKNOWN', 3, '2025-10-23 12:55:17+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (122, 'B-68F93720ACF83', 6, '2025-10-23 12:55:19+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (123, 'B-68F96B4B05AE3', 13, '2025-10-23 13:34:49+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (124, 'B-68F9651092F50', 9, '2025-10-23 14:13:20+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (125, 'B-68F96B4B05AE3', 13, '2025-10-23 14:44:36+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (126, 'UNKNOWN', 3, '2025-10-23 14:46:37+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (127, 'UNKNOWN', 2, '2025-10-23 14:46:39+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (128, 'UNKNOWN', 2, '2025-10-23 14:47:06+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (129, 'B-68F96B4B05AE3', 13, '2025-10-23 15:06:33+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (130, 'UNKNOWN', 3, '2025-10-23 16:10:47+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (131, 'UNKNOWN', 3, '2025-10-23 16:15:18+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (132, 'UNKNOWN', 3, '2025-10-23 16:15:35+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (133, 'UNKNOWN', 3, '2025-10-23 16:16:22+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (134, 'UNKNOWN', 2, '2025-10-23 16:23:24+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (135, 'UNKNOWN', 3, '2025-10-23 16:32:56+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (136, 'UNKNOWN', 3, '2025-10-23 16:33:19+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (137, 'UNKNOWN', 3, '2025-10-23 16:43:25+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (139, 'B-68F9651092F50', 9, '2025-10-23 17:08:28+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (140, 'UNKNOWN', 3, '2025-10-23 17:51:09+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (141, 'UNKNOWN', 3, '2025-10-23 18:01:21+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (142, 'UNKNOWN', 1, '2025-10-23 20:06:01+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (145, 'B-68F96B4B05AE3', 13, '2025-10-24 11:48:44+01', 'denied', 'Subscription expired or not yet active');
INSERT INTO pool_schema.access_logs VALUES (146, 'B-68F96967BBEE1', 12, '2025-10-24 11:48:57+01', 'denied', 'Subscription expired or not yet active');
INSERT INTO pool_schema.access_logs VALUES (147, 'B-68F94CC8AF409', 8, '2025-10-24 11:49:14+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (148, 'B-68F94CC8AF409', 8, '2025-10-24 11:49:18+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (149, 'B-68F94CC8AF409', 8, '2025-10-24 11:49:45+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (150, 'UNKNOWN', 7, '2025-10-24 11:49:48+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (151, 'B-68F96B4B05AE3', 13, '2025-10-24 12:47:13+01', 'denied', 'Subscription expired or not yet active');
INSERT INTO pool_schema.access_logs VALUES (152, 'B-68F96B4B05AE3', 13, '2025-10-24 12:47:16+01', 'denied', 'Subscription expired or not yet active');
INSERT INTO pool_schema.access_logs VALUES (153, 'B-68F96B4B05AE3', 13, '2025-10-24 12:47:20+01', 'denied', 'Subscription expired or not yet active');
INSERT INTO pool_schema.access_logs VALUES (154, 'B-68F96967BBEE1', 12, '2025-10-24 12:47:22+01', 'denied', 'Subscription expired or not yet active');
INSERT INTO pool_schema.access_logs VALUES (155, 'B-68F96967BBEE1', 12, '2025-10-24 12:47:26+01', 'denied', 'Subscription expired or not yet active');
INSERT INTO pool_schema.access_logs VALUES (156, 'B-68F9651092F50', 9, '2025-10-24 12:47:34+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (157, 'B-68F96967BBEE1', 12, '2025-10-24 12:48:12+01', 'denied', 'Subscription expired or not yet active');
INSERT INTO pool_schema.access_logs VALUES (158, 'B-68F96967BBEE1', 12, '2025-10-24 12:48:46+01', 'denied', 'Subscription expired or not yet active');
INSERT INTO pool_schema.access_logs VALUES (159, 'B-68F9651092F50', 9, '2025-10-24 12:48:47+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (160, 'B-68F9651092F50', 9, '2025-10-24 12:53:40+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (161, 'B-68F9651092F50', 9, '2025-10-24 12:53:46+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (162, 'B-68F96967BBEE1', 12, '2025-10-24 12:54:44+01', 'denied', 'Subscription expired or not yet active');
INSERT INTO pool_schema.access_logs VALUES (163, 'B-68F96967BBEE1', 12, '2025-10-24 13:02:43+01', 'denied', 'Subscription expired or not yet active');
INSERT INTO pool_schema.access_logs VALUES (164, 'B-68F96B4B05AE3', 13, '2025-10-24 13:02:46+01', 'denied', 'Subscription expired or not yet active');
INSERT INTO pool_schema.access_logs VALUES (166, 'B-68F96967BBEE1', 12, '2025-10-24 13:02:54+01', 'denied', 'Subscription expired or not yet active');
INSERT INTO pool_schema.access_logs VALUES (167, 'UNKNOWN', 7, '2025-10-24 13:03:02+01', 'denied', 'No badge assigned');
INSERT INTO pool_schema.access_logs VALUES (168, 'B-68F93720ACF83', 6, '2025-10-24 13:03:05+01', 'denied', 'Subscription expired or not yet active');
INSERT INTO pool_schema.access_logs VALUES (169, 'B-68F92FCC766BC', 5, '2025-10-24 13:03:07+01', 'denied', 'Subscription expired or not yet active');
INSERT INTO pool_schema.access_logs VALUES (170, 'B-68F96B4B05AE3', 13, '2025-10-24 13:09:09+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (171, 'B-68F96B4B05AE3', 13, '2025-10-24 13:09:14+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (172, 'B-68F96967BBEE1', 12, '2025-10-24 13:09:19+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (173, 'B-68F94CC8AF409', 8, '2025-10-24 13:09:23+01', 'denied', 'Jour non autorisé');
INSERT INTO pool_schema.access_logs VALUES (174, 'B-68F94CC8AF409', 8, '2025-10-24 13:09:26+01', 'denied', 'Jour non autorisé');
INSERT INTO pool_schema.access_logs VALUES (175, 'B-68F93720ACF83', 6, '2025-10-24 13:09:30+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (176, 'B-68F93720ACF83', 6, '2025-10-24 13:09:43+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (177, 'B-68F93720ACF83', 6, '2025-10-24 13:09:47+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (178, 'B-68F92FCC766BC', 5, '2025-10-24 13:09:52+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (179, 'UNKNOWN', 7, '2025-10-24 13:09:59+01', 'denied', 'Aucun badge assigné');
INSERT INTO pool_schema.access_logs VALUES (181, 'B-68F96B4B05AE3', 13, '2025-10-24 13:19:54+01', 'denied', 'Aucun abonnement actif');
INSERT INTO pool_schema.access_logs VALUES (182, 'B-68F96967BBEE1', 12, '2025-10-24 13:19:57+01', 'denied', 'Aucun abonnement actif');
INSERT INTO pool_schema.access_logs VALUES (183, 'B-68F9651092F50', 9, '2025-10-24 13:19:59+01', 'denied', 'Aucun abonnement actif');
INSERT INTO pool_schema.access_logs VALUES (185, 'B-68FA72FC5164C', 3, '2025-10-24 13:20:06+01', 'denied', 'Aucun abonnement actif');
INSERT INTO pool_schema.access_logs VALUES (187, 'B-68F96B4B05AE3', 13, '2025-10-24 14:21:02+01', 'denied', 'Aucun abonnement actif');
INSERT INTO pool_schema.access_logs VALUES (188, 'B-68FA72FC5164C', 3, '2025-10-24 15:06:04+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (190, 'B-68F92FCC766BC', 5, '2025-10-24 15:06:11+01', 'denied', 'Aucun abonnement actif');
INSERT INTO pool_schema.access_logs VALUES (191, 'B-68F93720ACF83', 6, '2025-10-24 15:06:14+01', 'denied', 'Aucun abonnement actif');
INSERT INTO pool_schema.access_logs VALUES (192, 'UNKNOWN', 7, '2025-10-24 15:06:16+01', 'denied', 'Aucun badge assigné');
INSERT INTO pool_schema.access_logs VALUES (193, 'B-68F94CC8AF409', 8, '2025-10-24 15:06:19+01', 'denied', 'Aucun abonnement actif');
INSERT INTO pool_schema.access_logs VALUES (194, 'B-68F9651092F50', 9, '2025-10-24 15:06:21+01', 'denied', 'Aucun abonnement actif');
INSERT INTO pool_schema.access_logs VALUES (195, 'B-68F96967BBEE1', 12, '2025-10-24 15:06:23+01', 'denied', 'Aucun abonnement actif');
INSERT INTO pool_schema.access_logs VALUES (196, 'B-68F96B4B05AE3', 13, '2025-10-24 15:06:25+01', 'denied', 'Aucun abonnement actif');
INSERT INTO pool_schema.access_logs VALUES (199, 'B-68FA72FC5164C', 3, '2025-10-24 16:52:09+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (200, 'B-68FA72FC5164C', 3, '2025-10-24 16:53:15+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (201, 'B-68FA72FC5164C', 3, '2025-10-24 17:06:18+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (202, 'B-68FA72FC5164C', 3, '2025-10-24 17:07:02+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (203, 'B-68FA72FC5164C', 3, '2025-10-24 17:07:20+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (204, 'B-68FA72FC5164C', 3, '2025-10-24 17:08:39+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (206, 'UNKNOWN', 7, '2025-10-24 17:34:13+01', 'denied', 'Aucun badge assigné');
INSERT INTO pool_schema.access_logs VALUES (207, 'B-68FA72FC5164C', 3, '2025-10-24 17:42:45+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (208, 'B-68FA72FC5164C', 3, '2025-10-24 17:43:30+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (209, 'B-68FA72FC5164C', 3, '2025-10-24 17:44:33+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (210, 'B-68F96B4B05AE3', 13, '2025-10-24 18:10:29+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (212, 'UNKNOWN', 7, '2025-10-24 18:10:34+01', 'denied', 'Aucun badge assigné');
INSERT INTO pool_schema.access_logs VALUES (213, 'B-68FA72FC5164C', 3, '2025-10-24 18:11:53+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (215, 'B-68F96967BBEE1', 12, '2025-10-24 18:29:54+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (217, 'UNKNOWN', 7, '2025-10-24 18:30:02+01', 'denied', 'Aucun badge assigné');
INSERT INTO pool_schema.access_logs VALUES (218, 'B-68FA72FC5164C', 3, '2025-10-24 18:30:10+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (219, 'UNKNOWN', 2, '2025-10-24 18:30:15+01', 'denied', 'Aucun badge assigné');
INSERT INTO pool_schema.access_logs VALUES (220, 'UNKNOWN', 1, '2025-10-24 18:30:16+01', 'denied', 'Aucun badge assigné');
INSERT INTO pool_schema.access_logs VALUES (221, 'UNKNOWN', 2, '2025-10-24 18:30:18+01', 'denied', 'Aucun badge assigné');
INSERT INTO pool_schema.access_logs VALUES (222, 'UNKNOWN', 1, '2025-10-24 18:30:20+01', 'denied', 'Aucun badge assigné');
INSERT INTO pool_schema.access_logs VALUES (223, 'B-68F96967BBEE1', 12, '2025-10-24 18:43:52+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (226, 'B-68FA72FC5164C', 3, '2025-10-24 19:54:48+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (228, 'B-68F92FCC766BC', 5, '2025-10-24 19:54:54+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (229, 'B-68F93720ACF83', 6, '2025-10-24 19:54:56+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (230, 'B-68FA72FC5164C', 3, '2025-10-24 20:00:06+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (231, 'B-68FA72FC5164C', 3, '2025-10-24 20:00:10+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (232, 'UID-3001', 17, '2025-10-24 23:14:23+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (233, 'UNKNOWN', 6, '2025-10-24 23:44:28+01', 'denied', 'Aucun badge assigné');
INSERT INTO pool_schema.access_logs VALUES (234, 'B-68FA72FC5164C', 3, '2025-10-24 23:44:49+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (235, 'B-68F94CC8AF409', 8, '2025-10-24 23:45:00+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (236, 'UID-4001', 18, '2025-10-25 10:40:19+01', 'denied', 'Jour non autorisé');
INSERT INTO pool_schema.access_logs VALUES (237, 'UID-4001', 18, '2025-10-25 10:40:24+01', 'denied', 'Jour non autorisé');
INSERT INTO pool_schema.access_logs VALUES (238, 'UID-4005', 23, '2025-10-26 00:21:52+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (241, 'B-68F96967BBEE1', 12, '2025-10-26 15:58:22+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (243, 'UNKNOWN', 6, '2025-10-26 15:59:37+01', 'denied', 'Aucun badge assigné');
INSERT INTO pool_schema.access_logs VALUES (244, 'UID-4006', 1, '2025-10-26 15:59:39+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (245, 'UID-1005', 2, '2025-10-26 15:59:41+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (246, 'UID-1001', 3, '2025-10-26 16:08:32+01', 'denied', 'Jour non autorisé');
INSERT INTO pool_schema.access_logs VALUES (248, 'UNKNOWN', 5, '2025-10-26 16:10:07+01', 'denied', 'Aucun badge assigné');
INSERT INTO pool_schema.access_logs VALUES (249, 'UNKNOWN', 6, '2025-10-26 16:10:11+01', 'denied', 'Aucun badge assigné');
INSERT INTO pool_schema.access_logs VALUES (250, 'UNKNOWN', 7, '2025-10-26 16:10:15+01', 'denied', 'Aucun badge assigné');
INSERT INTO pool_schema.access_logs VALUES (251, 'B-68F94CC8AF409', 8, '2025-10-26 16:10:18+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (252, 'B-68F9651092F50', 9, '2025-10-26 16:10:21+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (254, 'UID-1001', 3, '2025-10-26 17:04:05+01', 'denied', 'Jour non autorisé');
INSERT INTO pool_schema.access_logs VALUES (255, 'UID-1001', 3, '2025-10-26 17:04:39+01', 'denied', 'Jour non autorisé');
INSERT INTO pool_schema.access_logs VALUES (260, 'B-68FA45C356A1B', 21, '2025-10-27 23:21:44+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (261, 'UID-1001', 3, '2025-10-27 23:21:56+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (262, 'UID-1001', 3, '2025-10-27 23:22:37+01', 'granted', NULL);
INSERT INTO pool_schema.access_logs VALUES (263, 'UNKNOWN', 12, '2025-10-27 23:22:40+01', 'denied', 'Aucun badge assigné');
INSERT INTO pool_schema.access_logs VALUES (264, 'B-68F9651092F50', 9, '2025-11-01 17:02:43+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (265, 'UID-1001', 3, '2025-11-01 17:02:52+01', 'denied', 'Jour non autorisé');
INSERT INTO pool_schema.access_logs VALUES (266, 'UID-4005', 23, '2025-11-09 09:12:31+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (267, 'B-68FA45C356A1B', 21, '2025-11-09 09:12:34+01', 'denied', 'Abonnement expiré ou pas encore actif');
INSERT INTO pool_schema.access_logs VALUES (268, 'UNKNOWN', 17, '2025-11-09 09:12:37+01', 'denied', 'Aucun badge assigné');
INSERT INTO pool_schema.access_logs VALUES (269, 'B-68F96B4B05AE3', 13, '2025-11-09 09:12:40+01', 'denied', 'Abonnement expiré ou pas encore actif');


--
-- TOC entry 5451 (class 0 OID 17154)
-- Dependencies: 275
-- Data for Name: activities; Type: TABLE DATA; Schema: pool_schema; Owner: postgres
--

INSERT INTO pool_schema.activities VALUES (1, 'Séance hommes', 'Session réservée aux hommes', 'men', '#3b82f6', true);
INSERT INTO pool_schema.activities VALUES (2, 'Aquagym', 'Cours d’aquagym pour femmes', 'aquagym', '#10b981', true);
INSERT INTO pool_schema.activities VALUES (3, 'Séance femmes', 'Séance réservée aux femmes', 'women', '#ec4899', true);
INSERT INTO pool_schema.activities VALUES (4, 'Crèches / Écoles', 'Groupes scolaires ou crèches partenaires', 'group', '#8b5cf6', true);
INSERT INTO pool_schema.activities VALUES (5, 'Séance privée', 'Séances privées / coachings', 'private', '#f59e0b', true);
INSERT INTO pool_schema.activities VALUES (8, 'Séance Enfant', NULL, 'enfant', '#60a5fa', true);
INSERT INTO pool_schema.activities VALUES (9, 'Vide', 'Créneau libre pour réservation', NULL, '#9ca3af', true);
INSERT INTO pool_schema.activities VALUES (6, 'Entretien', 'Période de nettoyage (piscine fermée)', 'cleaning', '#9ca3af', true);


--
-- TOC entry 5459 (class 0 OID 17240)
-- Dependencies: 283
-- Data for Name: activity_plan_prices; Type: TABLE DATA; Schema: pool_schema; Owner: postgres
--

INSERT INTO pool_schema.activity_plan_prices VALUES (9, 1, 1, 800.00);
INSERT INTO pool_schema.activity_plan_prices VALUES (18, 1, 2, 4500.00);
INSERT INTO pool_schema.activity_plan_prices VALUES (19, 1, 3, 3500.00);
INSERT INTO pool_schema.activity_plan_prices VALUES (20, 2, 3, 3500.00);
INSERT INTO pool_schema.activity_plan_prices VALUES (21, 2, 2, 4500.00);
INSERT INTO pool_schema.activity_plan_prices VALUES (22, 2, 1, 800.00);
INSERT INTO pool_schema.activity_plan_prices VALUES (23, 3, 3, 3500.00);
INSERT INTO pool_schema.activity_plan_prices VALUES (24, 3, 2, 4500.00);
INSERT INTO pool_schema.activity_plan_prices VALUES (25, 3, 1, 800.00);
INSERT INTO pool_schema.activity_plan_prices VALUES (26, 5, 1, 1200.00);


--
-- TOC entry 5434 (class 0 OID 16750)
-- Dependencies: 245
-- Data for Name: audit_log; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO pool_schema.audit_log VALUES (1, 'pool_schema.staff', '9', 'UPDATE', 1, '2025-10-21 23:19:43.907001+01', '{"is_active": true}', '{"is_active": false}');
INSERT INTO pool_schema.audit_log VALUES (2, 'pool_schema.staff', '9', 'UPDATE', 1, '2025-10-21 23:20:01.138383+01', '{"is_active": false}', '{"is_active": true}');
INSERT INTO pool_schema.audit_log VALUES (3, 'pool_schema.staff', '9', 'UPDATE', 1, '2025-10-21 23:20:03.482342+01', '{"is_active": true}', '{"is_active": false}');
INSERT INTO pool_schema.audit_log VALUES (4, 'pool_schema.staff', '11', 'CREATE', 1, '2025-10-21 23:22:19.677578+01', NULL, '{"role_id": "5", "staff_id": 11, "username": "user1", "is_active": true, "last_name": "user", "created_at": "2025-10-21T22:22:19.653909Z", "first_name": "user", "password_hash": "$2y$12$W58SiunBh/18ygEfB.hZtuLa50OsSf3xdWieTnE5j9i8qWliifqZq"}');
INSERT INTO pool_schema.audit_log VALUES (5, 'pool_schema.members', '1', 'CREATE', 1, '2025-10-22 01:14:10.736647+01', NULL, '{"email": "kohil.abdelhak10@gmail.com", "address": "16000", "last_name": "memebr lastnae", "member_id": 1, "first_name": "member1", "phone_number": "+21310003258", "date_of_birth": "2025-10-23"}');
INSERT INTO pool_schema.audit_log VALUES (6, 'pool_schema.permissions', '3', 'CREATE', 1, '2025-10-22 13:43:27.172808+01', NULL, '{"permission_id": 3, "permission_name": "permision3"}');
INSERT INTO pool_schema.audit_log VALUES (7, 'pool_schema.roles', '6', 'CREATE', 1, '2025-10-22 13:44:14.907625+01', NULL, '{"role_id": 6, "role_name": "roletest"}');
INSERT INTO pool_schema.audit_log VALUES (8, 'pool_schema.roles', '6', 'UPDATE', 1, '2025-10-22 13:44:22.417407+01', '{"role_name": "roletest"}', '{"role_name": "roletest2"}');
INSERT INTO pool_schema.audit_log VALUES (9, 'pool_schema.roles', '6', 'DELETE', 1, '2025-10-22 13:44:35.146031+01', '{"role_id": 6, "role_name": "roletest2"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (10, 'pool_schema.permissions', '3', 'UPDATE', 1, '2025-10-22 13:45:13.789157+01', '{"permission_name": "permision3"}', '{"permission_name": "permision4"}');
INSERT INTO pool_schema.audit_log VALUES (11, 'pool_schema.permissions', '3', 'DELETE', 1, '2025-10-22 13:45:16.800257+01', '{"permission_id": 3, "permission_name": "permision4"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (12, 'pool_schema.members', '2', 'CREATE', 1, '2025-10-22 14:08:32.700715+01', NULL, '{"email": "member2@gmail.com", "address": "16000", "last_name": "m2", "member_id": 2, "first_name": "member 2", "phone_number": "0654875412"}');
INSERT INTO pool_schema.audit_log VALUES (13, 'pool_schema.members', '3', 'CREATE', 1, '2025-10-22 14:09:21.013404+01', NULL, '{"email": null, "address": "16000", "last_name": "Ka", "member_id": 3, "first_name": "Abdo", "phone_number": null}');
INSERT INTO pool_schema.audit_log VALUES (14, 'pool_schema.members', '3', 'UPDATE', 1, '2025-10-22 14:09:46.784517+01', '{"email": null, "phone_number": null}', '{"email": "abdo@gmail.com", "phone_number": "65847555"}');
INSERT INTO pool_schema.audit_log VALUES (15, 'pool_schema.plans', '1', 'CREATE', 1, '2025-10-22 14:16:04.539909+01', NULL, '{"price": "800", "plan_id": 1, "is_active": true, "plan_name": "natation par visite", "plan_type": "per_visit", "description": null, "duration_months": null, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (16, 'pool_schema.plans', '2', 'CREATE', 1, '2025-10-22 14:17:52.116123+01', NULL, '{"price": "4000", "plan_id": 2, "is_active": true, "plan_name": "natation 2 fois par semaine", "plan_type": "monthly_weekly", "description": null, "duration_months": "1", "visits_per_week": "2"}');
INSERT INTO pool_schema.audit_log VALUES (17, 'pool_schema.plans', '3', 'CREATE', 1, '2025-10-22 14:18:28.015271+01', NULL, '{"price": "3500", "plan_id": 3, "is_active": true, "plan_name": "natation 1 fois par semaine", "plan_type": "monthly_weekly", "description": null, "duration_months": "1", "visits_per_week": "1"}');
INSERT INTO pool_schema.audit_log VALUES (18, 'pool_schema.subscriptions', '1', 'CREATE', 1, '2025-10-22 14:22:30.030645+01', NULL, '{"status": "active", "plan_id": "1", "end_date": "2025-10-22", "member_id": "3", "start_date": "2025-10-22", "subscription_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (19, 'pool_schema.subscriptions', '2', 'CREATE', 1, '2025-10-22 14:22:55.322461+01', NULL, '{"status": "active", "plan_id": "3", "end_date": "2025-11-22", "member_id": "2", "start_date": "2025-10-22", "subscription_id": 2}');
INSERT INTO pool_schema.audit_log VALUES (20, 'pool_schema.payments', '1', 'CREATE', 1, '2025-10-22 14:29:06.77393+01', NULL, '{"notes": "test", "amount": "3500", "payment_id": 1, "payment_method": "cash", "subscription_id": "2", "received_by_staff_id": "1"}');
INSERT INTO pool_schema.audit_log VALUES (21, 'pool_schema.payments', '2', 'CREATE', 1, '2025-10-22 14:42:23.490387+01', NULL, '{"notes": "11", "amount": "3500", "payment_id": 2, "payment_method": "cash", "subscription_id": "2", "received_by_staff_id": "1"}');
INSERT INTO pool_schema.audit_log VALUES (22, 'pool_schema.payments', '1', 'DELETE', 1, '2025-10-22 14:46:27.441061+01', '{"notes": "test", "amount": "3500.00", "payment_id": 1, "payment_date": "2025-10-22 14:29:06.768404+01", "payment_method": "cash", "subscription_id": 2, "received_by_staff_id": 1}', NULL);
INSERT INTO pool_schema.audit_log VALUES (23, 'pool_schema.payments', '3', 'CREATE', 1, '2025-10-22 14:46:52.296299+01', NULL, '{"notes": "tgg", "amount": "4000", "payment_id": 3, "payment_method": "cash", "subscription_id": "1", "received_by_staff_id": "1"}');
INSERT INTO pool_schema.audit_log VALUES (24, 'pool_schema.access_logs', '1', 'CREATE', 1, '2025-10-22 15:02:01.953642+01', NULL, '{"log_id": 1, "badge_uid": "012547", "member_id": "3", "denial_reason": null, "access_decision": "granted"}');
INSERT INTO pool_schema.audit_log VALUES (25, 'pool_schema.access_logs', '2', 'CREATE', 1, '2025-10-22 15:02:16.383763+01', NULL, '{"log_id": 2, "badge_uid": "01225544", "member_id": "2", "denial_reason": null, "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (26, 'pool_schema.staff', '9', 'DELETE', 1, '2025-10-22 15:11:43.224003+01', '{"role_id": 1, "staff_id": 9, "username": "admin2", "is_active": false, "last_name": "ad", "created_at": "2025-10-21 20:24:50+01", "first_name": "admin1", "password_hash": "$2y$12$fbOZ8xUIJ.9pEJpj8qqOO.mr.rEABWxyURLXtJnNsGn0kNOrMyG4i"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (27, 'pool_schema.staff', '11', 'UPDATE', 1, '2025-10-22 15:17:09.438843+01', '{"is_active": true}', '{"is_active": false}');
INSERT INTO pool_schema.audit_log VALUES (28, 'pool_schema.staff', '12', 'CREATE', 1, '2025-10-22 15:17:38.339548+01', NULL, '{"role_id": "1", "staff_id": 12, "username": "admin2", "is_active": true, "last_name": "ad", "created_at": "2025-10-22T14:17:38.331699Z", "first_name": "admin2", "password_hash": "$2y$12$0MsVhf.bc638Tyqto4kJF.0tpHTUfpdCOn87YXtgFCBUGDY3/NZay"}');
INSERT INTO pool_schema.audit_log VALUES (29, 'pool_schema.subscriptions', '3', 'CREATE', 1, '2025-10-22 15:31:05.18677+01', NULL, '{"status": "active", "plan_id": 3, "end_date": "2025-10-22", "member_id": "3", "start_date": "2025-10-22", "subscription_id": 3}');
INSERT INTO pool_schema.audit_log VALUES (30, 'pool_schema.subscriptions', '1', 'UPDATE', 1, '2025-10-22 15:31:57.549574+01', '{"plan_id": 1}', '{"plan_id": 2}');
INSERT INTO pool_schema.audit_log VALUES (31, 'pool_schema.subscriptions', '2', 'UPDATE', 1, '2025-10-22 15:39:19.803259+01', '{"plan_id": 3}', '{"plan_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (32, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-22 15:59:52.586277+01', '{"status": "active"}', '{"status": "expired"}');
INSERT INTO pool_schema.audit_log VALUES (33, 'pool_schema.subscriptions', '2', 'UPDATE', 1, '2025-10-22 16:00:01.295203+01', '{"status": "active"}', '{"status": "expired"}');
INSERT INTO pool_schema.audit_log VALUES (34, 'pool_schema.subscriptions', '1', 'UPDATE', 1, '2025-10-22 16:00:09.613456+01', '{"status": "active"}', '{"status": "expired"}');
INSERT INTO pool_schema.audit_log VALUES (35, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-22 16:13:58.631+01', '{"plan_id": 3}', '{"plan_id": 2}');
INSERT INTO pool_schema.audit_log VALUES (36, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-22 16:22:46.742329+01', '{"status": "expired"}', '{"status": "active"}');
INSERT INTO pool_schema.audit_log VALUES (37, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-22 16:22:53.490112+01', '{"status": "active"}', '{"status": "expired"}');
INSERT INTO pool_schema.audit_log VALUES (38, 'subscriptions', '3', 'deactivated', 1, '2025-10-22 16:22:53.497862+01', '"{\"status\":\"active\"}"', '"{\"status\":\"expired\"}"');
INSERT INTO pool_schema.audit_log VALUES (39, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-22 16:23:05.866141+01', '{"status": "expired"}', '{"status": "active"}');
INSERT INTO pool_schema.audit_log VALUES (516, 'pool_schema.subscription_allowed_days', '9', 'DELETE', NULL, '2025-10-30 02:59:08.8424+01', '{"weekday_id": 2, "subscription_id": 9}', NULL);
INSERT INTO pool_schema.audit_log VALUES (40, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-22 16:23:24.117113+01', '{"end_date": "2025-10-22", "start_date": "2025-10-22"}', '{"end_date": "2025-10-21", "start_date": "2025-10-20"}');
INSERT INTO pool_schema.audit_log VALUES (41, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-22 16:31:09.72903+01', '{"status": "expired"}', '{"status": "active"}');
INSERT INTO pool_schema.audit_log VALUES (42, 'pool_schema.subscriptions', '2', 'UPDATE', 1, '2025-10-22 16:31:29.731677+01', '{"status": "expired", "end_date": "2025-11-22", "start_date": "2025-10-22"}', '{"status": "active", "end_date": "2025-10-21", "start_date": "2025-10-20"}');
INSERT INTO pool_schema.audit_log VALUES (43, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-22 16:46:48.609603+01', '{"status": "expired"}', '{"status": "active"}');
INSERT INTO pool_schema.audit_log VALUES (44, 'pool_schema.subscriptions', '2', 'UPDATE', 1, '2025-10-22 16:59:37.869752+01', '{"end_date": "2025-10-21"}', '{"end_date": "2025-10-23"}');
INSERT INTO pool_schema.audit_log VALUES (45, 'pool_schema.subscriptions', '2', 'UPDATE', 1, '2025-10-22 17:00:34.005755+01', '{"status": "expired"}', '{"status": "active"}');
INSERT INTO pool_schema.audit_log VALUES (46, 'pool_schema.members', '4', 'CREATE', 1, '2025-10-22 17:02:02.915037+01', NULL, '{"email": "user1@gmail.com", "address": "user address", "last_name": "user1", "member_id": 4, "first_name": "user1", "phone_number": "65847555"}');
INSERT INTO pool_schema.audit_log VALUES (47, 'pool_schema.subscriptions', '4', 'CREATE', 1, '2025-10-22 17:02:19.985565+01', NULL, '{"status": "active", "plan_id": 1, "end_date": "2025-10-22", "member_id": "4", "start_date": "2025-10-22", "subscription_id": 4, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (48, 'pool_schema.subscriptions', '4', 'DELETE', 1, '2025-10-22 17:31:13.313025+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-22", "member_id": 4, "paused_at": null, "created_at": "2025-10-22 17:02:19.985565+01", "resumes_at": null, "start_date": "2025-10-22", "deactivated_by": null, "subscription_id": 4, "visits_per_week": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (49, 'pool_schema.subscriptions', '5', 'CREATE', 1, '2025-10-22 17:31:33.250353+01', NULL, '{"status": "active", "plan_id": 1, "end_date": "2025-10-22", "member_id": "4", "start_date": "2025-10-22", "subscription_id": 5, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (50, 'pool_schema.subscriptions', '7', 'CREATE', 1, '2025-10-22 18:01:57.479035+01', NULL, '{"status": "active", "plan_id": 1, "end_date": "2025-10-22", "member_id": "3", "start_date": "2025-10-22", "subscription_id": 7, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (51, 'pool_schema.subscriptions', '5', 'UPDATE', 1, '2025-10-22 18:04:21.278729+01', '{"plan_id": 1, "visits_per_week": null}', '{"plan_id": 2, "visits_per_week": "2"}');
INSERT INTO pool_schema.audit_log VALUES (52, 'pool_schema.subscriptions', '8', 'CREATE', 1, '2025-10-22 18:28:49.906169+01', NULL, '{"status": "active", "plan_id": 3, "end_date": "2025-10-31", "member_id": "3", "start_date": "2025-10-22", "subscription_id": 8, "visits_per_week": "1"}');
INSERT INTO pool_schema.audit_log VALUES (53, 'pool_schema.subscriptions', '8', 'UPDATE', 1, '2025-10-22 18:47:52.890666+01', '{"status": "active", "updated_by": null}', '{"status": "paused", "updated_by": 1}');
INSERT INTO pool_schema.audit_log VALUES (54, 'pool_schema.access_logs', '3', 'CREATE', 1, '2025-10-22 20:22:56.529523+01', NULL, '{"log_id": 3, "badge_uid": "UNKNOWN", "member_id": 3, "access_time": "2025-10-22 19:22:56", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (55, 'pool_schema.access_logs', '4', 'CREATE', 1, '2025-10-22 20:23:02.80503+01', NULL, '{"log_id": 4, "badge_uid": "UNKNOWN", "member_id": 2, "access_time": "2025-10-22 19:23:02", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (56, 'pool_schema.access_logs', '5', 'CREATE', 1, '2025-10-22 20:23:10.327636+01', NULL, '{"log_id": 5, "badge_uid": "UNKNOWN", "member_id": 4, "access_time": "2025-10-22 19:23:10", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (57, 'pool_schema.access_logs', '6', 'CREATE', 1, '2025-10-22 20:23:14.610728+01', NULL, '{"log_id": 6, "badge_uid": "UNKNOWN", "member_id": 4, "access_time": "2025-10-22 19:23:14", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (58, 'pool_schema.access_logs', '7', 'CREATE', 1, '2025-10-22 20:23:26.189174+01', NULL, '{"log_id": 7, "badge_uid": "UNKNOWN", "member_id": 1, "access_time": "2025-10-22 19:23:26", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (59, 'pool_schema.access_logs', '8', 'CREATE', 1, '2025-10-22 20:23:30.539001+01', NULL, '{"log_id": 8, "badge_uid": "UNKNOWN", "member_id": 2, "access_time": "2025-10-22 19:23:30", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (60, 'pool_schema.access_logs', '9', 'CREATE', 1, '2025-10-22 20:23:35.736056+01', NULL, '{"log_id": 9, "badge_uid": "UNKNOWN", "member_id": 3, "access_time": "2025-10-22 19:23:35", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (61, 'pool_schema.access_logs', '10', 'CREATE', 1, '2025-10-22 20:24:11.629058+01', NULL, '{"log_id": 10, "badge_uid": "UNKNOWN", "member_id": 2, "access_time": "2025-10-22 19:24:11", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (62, 'pool_schema.access_logs', '11', 'CREATE', 1, '2025-10-22 20:24:18.896567+01', NULL, '{"log_id": 11, "badge_uid": "UNKNOWN", "member_id": 1, "access_time": "2025-10-22 19:24:18", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (63, 'pool_schema.members', '5', 'CREATE', 1, '2025-10-22 20:26:04.475727+01', NULL, '{"email": "user2@gmail.com", "last_name": "user2", "member_id": 5, "first_name": "user2", "phone_number": "+213556544474"}');
INSERT INTO pool_schema.audit_log VALUES (64, 'pool_schema.subscriptions', '9', 'CREATE', 1, '2025-10-22 20:26:04.475727+01', NULL, '{"status": "active", "plan_id": "3", "end_date": "2025-10-22", "member_id": 5, "created_by": 1, "start_date": "2025-10-22", "updated_by": 1, "subscription_id": 9}');
INSERT INTO pool_schema.audit_log VALUES (65, 'pool_schema.access_badges', '1', 'CREATE', 1, '2025-10-22 20:26:04.475727+01', NULL, '{"status": "active", "badge_id": 1, "badge_uid": "B-68F92FCC766BC", "issued_at": "2025-10-22T19:26:04.485074Z", "member_id": 5}');
INSERT INTO pool_schema.audit_log VALUES (66, 'pool_schema.access_logs', '12', 'CREATE', 1, '2025-10-22 20:26:18.288613+01', NULL, '{"log_id": 12, "badge_uid": "B-68F92FCC766BC", "member_id": 5, "access_time": "2025-10-22 19:26:18", "denial_reason": null, "access_decision": "granted"}');
INSERT INTO pool_schema.audit_log VALUES (67, 'pool_schema.subscriptions', '9', 'UPDATE', 1, '2025-10-22 20:26:54.116183+01', '{"visits_per_week": null}', '{"visits_per_week": "1"}');
INSERT INTO pool_schema.audit_log VALUES (68, 'pool_schema.subscriptions', '9', 'UPDATE', 1, '2025-10-22 20:27:19.518278+01', '{"status": "active"}', '{"status": "expired"}');
INSERT INTO pool_schema.audit_log VALUES (69, 'pool_schema.access_logs', '13', 'CREATE', 1, '2025-10-22 20:27:30.026658+01', NULL, '{"log_id": 13, "badge_uid": "B-68F92FCC766BC", "member_id": 5, "access_time": "2025-10-22 19:27:30", "denial_reason": "No active subscription", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (70, 'pool_schema.access_badges', '1', 'UPDATE', 1, '2025-10-22 20:28:49.005465+01', '{"status": "active"}', '{"status": "inactive"}');
INSERT INTO pool_schema.audit_log VALUES (71, 'pool_schema.access_logs', '14', 'CREATE', 1, '2025-10-22 20:28:52.31235+01', NULL, '{"log_id": 14, "badge_uid": "B-68F92FCC766BC", "member_id": 5, "access_time": "2025-10-22 19:28:52", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (72, 'pool_schema.access_badges', '1', 'UPDATE', 1, '2025-10-22 20:28:56.910683+01', '{"status": "inactive"}', '{"status": "lost"}');
INSERT INTO pool_schema.audit_log VALUES (73, 'pool_schema.access_logs', '15', 'CREATE', 1, '2025-10-22 20:28:57.483959+01', NULL, '{"log_id": 15, "badge_uid": "B-68F92FCC766BC", "member_id": 5, "access_time": "2025-10-22 19:28:57", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (74, 'pool_schema.access_badges', '1', 'UPDATE', 1, '2025-10-22 20:29:01.898853+01', '{"status": "lost"}', '{"status": "revoked"}');
INSERT INTO pool_schema.audit_log VALUES (75, 'pool_schema.access_logs', '16', 'CREATE', 1, '2025-10-22 20:29:02.507942+01', NULL, '{"log_id": 16, "badge_uid": "B-68F92FCC766BC", "member_id": 5, "access_time": "2025-10-22 19:29:02", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (76, 'pool_schema.access_logs', '17', 'CREATE', 1, '2025-10-22 20:29:05.041773+01', NULL, '{"log_id": 17, "badge_uid": "B-68F92FCC766BC", "member_id": 5, "access_time": "2025-10-22 19:29:05", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (77, 'pool_schema.access_badges', '1', 'UPDATE', 1, '2025-10-22 20:29:10.369383+01', '{"status": "revoked"}', '{"status": "active"}');
INSERT INTO pool_schema.audit_log VALUES (78, 'pool_schema.access_logs', '18', 'CREATE', 1, '2025-10-22 20:29:11.722721+01', NULL, '{"log_id": 18, "badge_uid": "B-68F92FCC766BC", "member_id": 5, "access_time": "2025-10-22 19:29:11", "denial_reason": "No active subscription", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (79, 'pool_schema.access_logs', '19', 'CREATE', 1, '2025-10-22 20:29:12.454934+01', NULL, '{"log_id": 19, "badge_uid": "B-68F92FCC766BC", "member_id": 5, "access_time": "2025-10-22 19:29:12", "denial_reason": "No active subscription", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (80, 'pool_schema.subscriptions', '9', 'UPDATE', 1, '2025-10-22 20:38:10.671561+01', '{"status": "expired"}', '{"status": "active"}');
INSERT INTO pool_schema.audit_log VALUES (81, 'pool_schema.access_logs', '20', 'CREATE', 1, '2025-10-22 20:42:38.887111+01', NULL, '{"log_id": 20, "badge_uid": "UNKNOWN", "member_id": 3, "access_time": "2025-10-22 19:42:38", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (82, 'pool_schema.members', '6', 'CREATE', 1, '2025-10-22 20:57:20.696105+01', NULL, '{"email": "user3@gmail.com", "last_name": "user3", "member_id": 6, "first_name": "user3", "phone_number": "03215478"}');
INSERT INTO pool_schema.audit_log VALUES (83, 'pool_schema.subscriptions', '10', 'CREATE', 1, '2025-10-22 20:57:20.696105+01', NULL, '{"status": "active", "plan_id": "3", "end_date": "2025-10-22", "member_id": 6, "created_by": 1, "start_date": "2025-10-22", "updated_by": 1, "subscription_id": 10}');
INSERT INTO pool_schema.audit_log VALUES (84, 'pool_schema.access_badges', '2', 'CREATE', 1, '2025-10-22 20:57:20.696105+01', NULL, '{"status": "active", "badge_id": 2, "badge_uid": "B-68F93720ACF83", "issued_at": "2025-10-22T19:57:20.708527Z", "member_id": 6}');
INSERT INTO pool_schema.audit_log VALUES (85, 'pool_schema.access_logs', '21', 'CREATE', 1, '2025-10-22 21:05:34.740642+01', NULL, '{"log_id": 21, "badge_uid": "UNKNOWN", "member_id": 3, "access_time": "2025-10-22 20:05:34", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (86, 'pool_schema.access_logs', '22', 'CREATE', 1, '2025-10-22 21:05:45.950461+01', NULL, '{"log_id": 22, "badge_uid": "UNKNOWN", "member_id": 1, "access_time": "2025-10-22 20:05:45", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (87, 'pool_schema.access_logs', '23', 'CREATE', 1, '2025-10-22 21:05:48.849607+01', NULL, '{"log_id": 23, "badge_uid": "B-68F93720ACF83", "member_id": 6, "access_time": "2025-10-22 20:05:48", "denial_reason": null, "access_decision": "granted"}');
INSERT INTO pool_schema.audit_log VALUES (88, 'pool_schema.access_logs', '24', 'CREATE', 1, '2025-10-22 21:05:55.023446+01', NULL, '{"log_id": 24, "badge_uid": "B-68F93720ACF83", "member_id": 6, "access_time": "2025-10-22 20:05:55", "denial_reason": null, "access_decision": "granted"}');
INSERT INTO pool_schema.audit_log VALUES (89, 'pool_schema.access_logs', '25', 'CREATE', 1, '2025-10-22 21:14:09.87562+01', NULL, '{"log_id": 25, "badge_uid": "B-68F93720ACF83", "member_id": 6, "access_time": "2025-10-22 20:14:09", "denial_reason": null, "access_decision": "granted"}');
INSERT INTO pool_schema.audit_log VALUES (90, 'pool_schema.access_logs', '26', 'CREATE', 1, '2025-10-22 21:15:19.04365+01', NULL, '{"log_id": 26, "badge_uid": "UNKNOWN", "member_id": 4, "access_time": "2025-10-22 20:15:19", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (91, 'pool_schema.members', '7', 'CREATE', 1, '2025-10-22 21:17:00.543274+01', NULL, '{"email": "user4@gmail.com", "last_name": "user4", "member_id": 7, "first_name": "user4", "phone_number": "0651245478"}');
INSERT INTO pool_schema.audit_log VALUES (92, 'pool_schema.subscriptions', '11', 'CREATE', 1, '2025-10-22 21:17:00.543274+01', NULL, '{"status": "active", "plan_id": "1", "end_date": "2025-10-22", "member_id": 7, "created_by": 1, "start_date": "2025-10-22", "updated_by": 1, "subscription_id": 11}');
INSERT INTO pool_schema.audit_log VALUES (93, 'pool_schema.access_badges', '3', 'CREATE', 1, '2025-10-22 21:17:00.543274+01', NULL, '{"status": "active", "badge_id": 3, "badge_uid": "B-68F93BBC8B4E9", "issued_at": "2025-10-22T20:17:00.570644Z", "member_id": 7}');
INSERT INTO pool_schema.audit_log VALUES (94, 'pool_schema.access_logs', '27', 'CREATE', 1, '2025-10-22 21:26:22.03156+01', NULL, '{"log_id": 27, "badge_uid": "B-68F93BBC8B4E9", "member_id": 7, "access_time": "2025-10-22 20:26:22", "denial_reason": null, "access_decision": "granted"}');
INSERT INTO pool_schema.audit_log VALUES (95, 'pool_schema.access_logs', '28', 'CREATE', 1, '2025-10-22 21:31:30.234879+01', NULL, '{"log_id": 28, "badge_uid": "UNKNOWN", "member_id": 3, "access_time": "2025-10-22 20:31:30", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (96, 'pool_schema.access_logs', '29', 'CREATE', 1, '2025-10-22 21:31:35.727183+01', NULL, '{"log_id": 29, "badge_uid": "UNKNOWN", "member_id": 4, "access_time": "2025-10-22 20:31:35", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (97, 'pool_schema.access_logs', '30', 'CREATE', 1, '2025-10-22 21:31:39.009219+01', NULL, '{"log_id": 30, "badge_uid": "B-68F93720ACF83", "member_id": 6, "access_time": "2025-10-22 20:31:38", "denial_reason": null, "access_decision": "granted"}');
INSERT INTO pool_schema.audit_log VALUES (98, 'pool_schema.access_logs', '31', 'CREATE', 1, '2025-10-22 21:32:52.979439+01', NULL, '{"log_id": 31, "badge_uid": "UNKNOWN", "member_id": 4, "access_time": "2025-10-22 20:32:52", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (99, 'pool_schema.access_logs', '32', 'CREATE', 1, '2025-10-22 21:32:58.115412+01', NULL, '{"log_id": 32, "badge_uid": "B-68F93720ACF83", "member_id": 6, "access_time": "2025-10-22 20:32:58", "denial_reason": null, "access_decision": "granted"}');
INSERT INTO pool_schema.audit_log VALUES (100, 'pool_schema.access_logs', '33', 'CREATE', 1, '2025-10-22 21:33:04.86785+01', NULL, '{"log_id": 33, "badge_uid": "B-68F93BBC8B4E9", "member_id": 7, "access_time": "2025-10-22 20:33:04", "denial_reason": null, "access_decision": "granted"}');
INSERT INTO pool_schema.audit_log VALUES (101, 'pool_schema.access_logs', '34', 'CREATE', 1, '2025-10-22 21:33:08.773374+01', NULL, '{"log_id": 34, "badge_uid": "B-68F92FCC766BC", "member_id": 5, "access_time": "2025-10-22 20:33:08", "denial_reason": null, "access_decision": "granted"}');
INSERT INTO pool_schema.audit_log VALUES (102, 'pool_schema.access_logs', '35', 'CREATE', 1, '2025-10-22 21:33:13.058546+01', NULL, '{"log_id": 35, "badge_uid": "UNKNOWN", "member_id": 3, "access_time": "2025-10-22 20:33:13", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (103, 'pool_schema.access_logs', '36', 'CREATE', 1, '2025-10-22 21:33:15.993169+01', NULL, '{"log_id": 36, "badge_uid": "UNKNOWN", "member_id": 2, "access_time": "2025-10-22 20:33:15", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (104, 'pool_schema.access_logs', '37', 'CREATE', 1, '2025-10-22 21:33:18.368925+01', NULL, '{"log_id": 37, "badge_uid": "UNKNOWN", "member_id": 1, "access_time": "2025-10-22 20:33:18", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (105, 'pool_schema.access_logs', '38', 'CREATE', 1, '2025-10-22 21:33:20.883131+01', NULL, '{"log_id": 38, "badge_uid": "B-68F93720ACF83", "member_id": 6, "access_time": "2025-10-22 20:33:20", "denial_reason": null, "access_decision": "granted"}');
INSERT INTO pool_schema.audit_log VALUES (106, 'pool_schema.members', '1', 'UPDATE', 1, '2025-10-22 21:33:40.392988+01', '{"last_name": "memebr lastnae"}', '{"last_name": "m"}');
INSERT INTO pool_schema.audit_log VALUES (107, 'pool_schema.members', '8', 'CREATE', 1, '2025-10-22 22:29:44.704433+01', NULL, '{"email": "user@gmail.com", "last_name": "u5", "member_id": 8, "first_name": "user5", "phone_number": "+21310003258"}');
INSERT INTO pool_schema.audit_log VALUES (108, 'pool_schema.subscriptions', '12', 'CREATE', 1, '2025-10-22 22:29:44.704433+01', NULL, '{"status": "active", "plan_id": "2", "end_date": "2025-11-22", "member_id": 8, "created_by": 1, "start_date": "2025-10-22", "updated_by": 1, "subscription_id": 12}');
INSERT INTO pool_schema.audit_log VALUES (109, 'pool_schema.access_badges', '4', 'CREATE', 1, '2025-10-22 22:29:44.704433+01', NULL, '{"status": "active", "badge_id": 4, "badge_uid": "B-68F94CC8AF409", "issued_at": "2025-10-22T21:29:44.717887Z", "member_id": 8}');
INSERT INTO pool_schema.audit_log VALUES (110, 'pool_schema.subscriptions', '13', 'CREATE', 1, '2025-10-22 23:40:16.086055+01', NULL, '{"status": "active", "plan_id": 3, "end_date": "2025-10-22", "member_id": "8", "created_by": 1, "start_date": "2025-10-22", "updated_by": 1, "subscription_id": 13, "visits_per_week": "1"}');
INSERT INTO pool_schema.audit_log VALUES (111, 'pool_schema.members', '9', 'CREATE', 1, '2025-10-23 00:13:20.555796+01', NULL, '{"email": "user6@gmail.com", "last_name": "u6", "member_id": 9, "first_name": "user6", "phone_number": "+21310003258"}');
INSERT INTO pool_schema.audit_log VALUES (112, 'pool_schema.subscriptions', '14', 'CREATE', 1, '2025-10-23 00:13:20.555796+01', NULL, '{"status": "active", "plan_id": "2", "end_date": "2025-10-24", "member_id": 9, "created_by": 1, "start_date": "2025-10-23", "updated_by": 1, "subscription_id": 14}');
INSERT INTO pool_schema.audit_log VALUES (113, 'pool_schema.access_badges', '5', 'CREATE', 1, '2025-10-23 00:13:20.555796+01', NULL, '{"status": "active", "badge_id": 5, "badge_uid": "B-68F9651092F50", "issued_at": "2025-10-22T23:13:20.602008Z", "member_id": 9}');
INSERT INTO pool_schema.audit_log VALUES (114, 'pool_schema.members', '10', 'CREATE', 1, '2025-10-23 00:14:09.771012+01', NULL, '{"email": "user7@gmail.com", "last_name": "u7", "member_id": 10, "first_name": "user7", "phone_number": "+21310003258"}');
INSERT INTO pool_schema.audit_log VALUES (115, 'pool_schema.subscriptions', '15', 'CREATE', 1, '2025-10-23 00:14:09.771012+01', NULL, '{"status": "active", "plan_id": "3", "end_date": "2025-10-31", "member_id": 10, "created_by": 1, "start_date": "2025-10-23", "updated_by": 1, "subscription_id": 15}');
INSERT INTO pool_schema.audit_log VALUES (116, 'pool_schema.access_badges', '6', 'CREATE', 1, '2025-10-23 00:14:09.771012+01', NULL, '{"status": "active", "badge_id": 6, "badge_uid": "B-68F96541C6A63", "issued_at": "2025-10-22T23:14:09.813724Z", "member_id": 10}');
INSERT INTO pool_schema.audit_log VALUES (117, 'pool_schema.members', '11', 'CREATE', 1, '2025-10-23 00:14:35.826113+01', NULL, '{"email": "user8@gmail.com", "last_name": "u8", "member_id": 11, "first_name": "user8", "phone_number": "+21310003258"}');
INSERT INTO pool_schema.audit_log VALUES (118, 'pool_schema.subscriptions', '16', 'CREATE', 1, '2025-10-23 00:14:35.826113+01', NULL, '{"status": "active", "plan_id": "1", "end_date": "2025-10-23", "member_id": 11, "created_by": 1, "start_date": "2025-10-23", "updated_by": 1, "subscription_id": 16}');
INSERT INTO pool_schema.audit_log VALUES (119, 'pool_schema.access_badges', '7', 'CREATE', 1, '2025-10-23 00:14:35.826113+01', NULL, '{"status": "active", "badge_id": 7, "badge_uid": "B-68F9655BD0DCD", "issued_at": "2025-10-22T23:14:35.855555Z", "member_id": 11}');
INSERT INTO pool_schema.audit_log VALUES (120, 'pool_schema.members', '12', 'CREATE', 1, '2025-10-23 00:31:51.715986+01', NULL, '{"email": "user10@gmail.com", "last_name": "u10", "member_id": 12, "created_by": 1, "first_name": "user10", "updated_by": 1, "phone_number": "+21310003258"}');
INSERT INTO pool_schema.audit_log VALUES (121, 'pool_schema.subscriptions', '17', 'CREATE', 1, '2025-10-23 00:31:51.715986+01', NULL, '{"status": "active", "plan_id": "1", "end_date": "2025-10-23", "member_id": 12, "created_by": 1, "start_date": "2025-10-23", "updated_by": 1, "subscription_id": 17}');
INSERT INTO pool_schema.audit_log VALUES (122, 'pool_schema.access_badges', '8', 'CREATE', 1, '2025-10-23 00:31:51.715986+01', NULL, '{"status": "active", "badge_id": 8, "badge_uid": "B-68F96967BBEE1", "issued_at": "2025-10-22T23:31:51.769810Z", "member_id": 12}');
INSERT INTO pool_schema.audit_log VALUES (123, 'pool_schema.members', '13', 'CREATE', 1, '2025-10-23 00:39:54.984917+01', NULL, '{"email": "user11@gmail.com", "last_name": "u11", "member_id": 13, "created_by": 1, "first_name": "user11", "updated_by": 1, "phone_number": "02154785"}');
INSERT INTO pool_schema.audit_log VALUES (124, 'pool_schema.subscriptions', '18', 'CREATE', 1, '2025-10-23 00:39:54.984917+01', NULL, '{"status": "active", "plan_id": "1", "end_date": "2025-10-23", "member_id": 13, "created_by": 1, "start_date": "2025-10-23", "updated_by": 1, "subscription_id": 18}');
INSERT INTO pool_schema.audit_log VALUES (125, 'pool_schema.access_badges', '9', 'CREATE', 1, '2025-10-23 00:39:54.984917+01', NULL, '{"status": "active", "badge_id": 9, "badge_uid": "B-68F96B4B05AE3", "issued_at": "2025-10-22T23:39:55.023343Z", "member_id": 13}');
INSERT INTO pool_schema.audit_log VALUES (126, 'pool_schema.access_badges', '7', 'DELETE', 1, '2025-10-23 00:54:29.879754+01', '{"status": "active", "badge_id": 7, "badge_uid": "B-68F9655BD0DCD", "issued_at": "2025-10-22 23:14:35+01", "member_id": 11, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (127, 'pool_schema.subscriptions', '16', 'DELETE', 1, '2025-10-23 00:54:29.903063+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-23", "member_id": 11, "paused_at": null, "created_at": "2025-10-23 00:14:35.826113+01", "created_by": 1, "resumes_at": null, "start_date": "2025-10-23", "updated_by": 1, "deactivated_by": null, "subscription_id": 16, "visits_per_week": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (128, 'pool_schema.members', '11', 'DELETE', 1, '2025-10-23 00:54:29.914774+01', '{"email": "user8@gmail.com", "address": null, "last_name": "u8", "member_id": 11, "created_at": "2025-10-23 00:14:35.826113+01", "created_by": null, "first_name": "user8", "updated_at": "2025-10-23 00:14:35.826113+01", "updated_by": null, "phone_number": "+21310003258", "date_of_birth": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (129, 'pool_schema.access_badges', '6', 'DELETE', 1, '2025-10-23 00:54:41.573151+01', '{"status": "active", "badge_id": 6, "badge_uid": "B-68F96541C6A63", "issued_at": "2025-10-22 23:14:09+01", "member_id": 10, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (130, 'pool_schema.subscriptions', '15', 'DELETE', 1, '2025-10-23 00:54:41.593849+01', '{"status": "active", "plan_id": 3, "end_date": "2025-10-31", "member_id": 10, "paused_at": null, "created_at": "2025-10-23 00:14:09.771012+01", "created_by": 1, "resumes_at": null, "start_date": "2025-10-23", "updated_by": 1, "deactivated_by": null, "subscription_id": 15, "visits_per_week": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (201, 'pool_schema.subscriptions', '24', 'UPDATE', 1, '2025-10-25 12:18:47.415801+01', '{"status": "paused", "updated_at": "2025-10-25 11:18:33+01"}', '{"status": "active", "updated_at": "2025-10-25T11:18:47.426002Z"}');
INSERT INTO pool_schema.audit_log VALUES (131, 'pool_schema.members', '10', 'DELETE', 1, '2025-10-23 00:54:41.601791+01', '{"email": "user7@gmail.com", "address": null, "last_name": "u7", "member_id": 10, "created_at": "2025-10-23 00:14:09.771012+01", "created_by": null, "first_name": "user7", "updated_at": "2025-10-23 00:14:09.771012+01", "updated_by": null, "phone_number": "+21310003258", "date_of_birth": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (132, 'pool_schema.access_logs', '39', 'CREATE', 1, '2025-10-23 01:09:36.241982+01', NULL, '{"log_id": 39, "badge_uid": "UNKNOWN", "member_id": 2, "access_time": "2025-10-23 00:09:36", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (133, 'pool_schema.access_logs', '40', 'CREATE', 1, '2025-10-23 01:09:49.493633+01', NULL, '{"log_id": 40, "badge_uid": "B-68F9651092F50", "member_id": 9, "access_time": "2025-10-23 00:09:49", "denial_reason": null, "access_decision": "granted"}');
INSERT INTO pool_schema.audit_log VALUES (134, 'pool_schema.access_logs', '41', 'CREATE', 1, '2025-10-23 01:26:55.429831+01', NULL, '{"log_id": 41, "badge_uid": "UNKNOWN", "member_id": 3, "access_time": "2025-10-23 00:26:55", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}');
INSERT INTO pool_schema.audit_log VALUES (135, 'pool_schema.access_logs', '42', 'CREATE', 1, '2025-10-23 01:27:42.918349+01', NULL, '{"log_id": 42, "badge_uid": "B-68F94CC8AF409", "member_id": 8, "access_time": "2025-10-23 00:27:42", "denial_reason": null, "access_decision": "granted"}');
INSERT INTO pool_schema.audit_log VALUES (136, 'pool_schema.subscriptions', '7', 'DELETE', 1, '2025-10-23 12:38:00.047142+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-22", "member_id": 3, "paused_at": null, "created_at": "2025-10-22 18:01:57.479035+01", "created_by": null, "resumes_at": null, "start_date": "2025-10-22", "updated_by": null, "deactivated_by": null, "subscription_id": 7, "visits_per_week": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (137, 'pool_schema.members', '14', 'CREATE', 1, '2025-10-23 16:12:03.140689+01', NULL, '{"email": "user12@gmail.com", "last_name": "u12", "member_id": 14, "created_by": 1, "first_name": "user12", "updated_by": 1, "phone_number": "03215478"}');
INSERT INTO pool_schema.audit_log VALUES (138, 'pool_schema.subscriptions', '19', 'CREATE', 1, '2025-10-23 16:12:03.140689+01', NULL, '{"status": "active", "plan_id": "1", "end_date": "2025-10-23", "member_id": 14, "created_by": 1, "start_date": "2025-10-23", "updated_by": 1, "subscription_id": 19}');
INSERT INTO pool_schema.audit_log VALUES (139, 'pool_schema.access_badges', '10', 'CREATE', 1, '2025-10-23 16:12:03.140689+01', NULL, '{"status": "active", "badge_id": 10, "badge_uid": "B-68FA45C356A1B", "issued_at": "2025-10-23T15:12:03.354889Z", "member_id": 14}');
INSERT INTO pool_schema.audit_log VALUES (140, 'pool_schema.access_badges', '11', 'CREATE', 1, '2025-10-23 19:25:00.308584+01', NULL, '{"status": "active", "badge_id": 11, "badge_uid": "B-68FA72FC5164C", "issued_at": "2025-10-23T18:25:00.333422Z", "member_id": 3}');
INSERT INTO pool_schema.audit_log VALUES (141, 'pool_schema.subscriptions', '19', 'UPDATE', 1, '2025-10-23 19:47:33.198107+01', '{"end_date": "2025-10-23T00:00:00.000000Z"}', '{"end_date": "2025-10-24 00:00:00"}');
INSERT INTO pool_schema.audit_log VALUES (142, 'pool_schema.subscriptions', '19', 'UPDATE', 1, '2025-10-23 19:48:01.858601+01', '{"end_date": "2025-10-24T00:00:00.000000Z"}', '{"end_date": "2025-10-26 00:00:00"}');
INSERT INTO pool_schema.audit_log VALUES (143, 'pool_schema.subscriptions', '8', 'UPDATE', 1, '2025-10-23 19:53:59.455368+01', '{"end_date": "2025-10-31T00:00:00.000000Z", "start_date": "2025-10-22T00:00:00.000000Z"}', '{"end_date": "2025-10-23 00:00:00", "start_date": "2025-10-23 00:00:00"}');
INSERT INTO pool_schema.audit_log VALUES (144, 'pool_schema.subscriptions', '14', 'UPDATE', 1, '2025-10-23 20:04:50.183332+01', '{"visits_per_week": null}', '{"visits_per_week": "2"}');
INSERT INTO pool_schema.audit_log VALUES (145, 'pool_schema.subscriptions', '1', 'UPDATE', 1, '2025-10-23 20:05:28.625422+01', '{"updated_by": null, "visits_per_week": null}', '{"updated_by": 1, "visits_per_week": "2"}');
INSERT INTO pool_schema.audit_log VALUES (146, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-23 20:05:46.294356+01', '{"updated_by": null, "visits_per_week": null}', '{"updated_by": 1, "visits_per_week": "2"}');
INSERT INTO pool_schema.audit_log VALUES (147, 'pool_schema.subscriptions', '10', 'UPDATE', 1, '2025-10-23 20:05:58.1356+01', '{"visits_per_week": null}', '{"visits_per_week": "1"}');
INSERT INTO pool_schema.audit_log VALUES (148, 'pool_schema.subscriptions', '12', 'UPDATE', 1, '2025-10-23 20:06:07.951201+01', '{"visits_per_week": null}', '{"visits_per_week": "2"}');
INSERT INTO pool_schema.audit_log VALUES (149, 'pool_schema.access_badges', '3', 'DELETE', 1, '2025-10-23 20:11:42.237866+01', '{"status": "active", "badge_id": 3, "badge_uid": "B-68F93BBC8B4E9", "issued_at": "2025-10-22 20:17:00+01", "member_id": 7, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (150, 'pool_schema.subscriptions', '11', 'DELETE', 1, '2025-10-23 20:11:42.258295+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-22", "member_id": 7, "paused_at": null, "created_at": "2025-10-22 21:17:00.543274+01", "created_by": 1, "resumes_at": null, "start_date": "2025-10-22", "updated_by": 1, "deactivated_by": null, "subscription_id": 11, "visits_per_week": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (151, 'pool_schema.subscriptions', '19', 'UPDATE', 1, '2025-10-24 14:03:39.529473+01', '{"plan_id": 1, "visits_per_week": null}', '{"plan_id": 2, "visits_per_week": "2"}');
INSERT INTO pool_schema.audit_log VALUES (152, 'pool_schema.subscriptions', '19', 'UPDATE', 1, '2025-10-24 14:16:28.591387+01', '{"status": "active"}', '{"status": "paused"}');
INSERT INTO pool_schema.audit_log VALUES (153, 'pool_schema.subscriptions', '18', 'UPDATE', 1, '2025-10-24 14:16:35.808178+01', '{"status": "active"}', '{"status": "paused"}');
INSERT INTO pool_schema.audit_log VALUES (154, 'pool_schema.subscriptions', '17', 'UPDATE', 1, '2025-10-24 14:16:42.449438+01', '{"status": "active"}', '{"status": "expired"}');
INSERT INTO pool_schema.audit_log VALUES (155, 'pool_schema.subscriptions', '14', 'UPDATE', 1, '2025-10-24 14:16:51.534563+01', '{"status": "active"}', '{"status": "cancelled"}');
INSERT INTO pool_schema.audit_log VALUES (156, 'pool_schema.subscriptions', '13', 'UPDATE', 1, '2025-10-24 14:16:58.611002+01', '{"status": "active"}', '{"status": "expired"}');
INSERT INTO pool_schema.audit_log VALUES (157, 'pool_schema.subscriptions', '12', 'UPDATE', 1, '2025-10-24 14:18:50.59845+01', '{"status": "active"}', '{"status": "paused"}');
INSERT INTO pool_schema.audit_log VALUES (158, 'pool_schema.subscriptions', '10', 'UPDATE', 1, '2025-10-24 14:18:58.350787+01', '{"status": "active"}', '{"status": "cancelled"}');
INSERT INTO pool_schema.audit_log VALUES (159, 'pool_schema.subscriptions', '9', 'UPDATE', 1, '2025-10-24 14:19:07.385682+01', '{"status": "active"}', '{"status": "expired"}');
INSERT INTO pool_schema.audit_log VALUES (160, 'pool_schema.subscriptions', '5', 'UPDATE', 1, '2025-10-24 14:19:20.325917+01', '{"status": "active", "updated_by": null}', '{"status": "paused", "updated_by": 1}');
INSERT INTO pool_schema.audit_log VALUES (161, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-24 14:19:28.091456+01', '{"status": "active"}', '{"status": "expired"}');
INSERT INTO pool_schema.audit_log VALUES (162, 'pool_schema.subscriptions', '2', 'UPDATE', 1, '2025-10-24 14:19:40.588929+01', '{"status": "active", "updated_by": null}', '{"status": "paused", "updated_by": 1}');
INSERT INTO pool_schema.audit_log VALUES (163, 'pool_schema.subscriptions', '8', 'UPDATE', 1, '2025-10-24 14:23:40.970012+01', '{"status": "paused"}', '{"status": "active"}');
INSERT INTO pool_schema.audit_log VALUES (164, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-24 14:24:26.880632+01', '{"plan_id": 2}', '{"plan_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (165, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-24 14:24:39.542476+01', '{"status": "expired"}', '{"status": "active"}');
INSERT INTO pool_schema.audit_log VALUES (166, 'pool_schema.subscriptions', '1', 'UPDATE', 1, '2025-10-24 14:40:01.490187+01', '{"status": "expired", "plan_id": 2}', '{"status": "active", "plan_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (167, 'pool_schema.subscriptions', '1', 'UPDATE', 1, '2025-10-24 14:43:13.033476+01', '{"status": "active"}', '{"status": "paused"}');
INSERT INTO pool_schema.audit_log VALUES (168, 'pool_schema.subscriptions', '8', 'UPDATE', 1, '2025-10-24 14:43:34.438043+01', '{"status": "active"}', '{"status": "paused"}');
INSERT INTO pool_schema.audit_log VALUES (169, 'pool_schema.subscriptions', '20', 'CREATE', 1, '2025-10-24 15:02:08.110274+01', NULL, '{"status": "active", "plan_id": 3, "end_date": "2025-10-25 00:00:00", "member_id": "4", "created_by": 1, "start_date": "2025-10-24 00:00:00", "updated_by": 1, "subscription_id": 20, "visits_per_week": "1"}');
INSERT INTO pool_schema.audit_log VALUES (170, 'pool_schema.subscriptions', '21', 'CREATE', 1, '2025-10-24 15:15:37.607402+01', NULL, '{"status": "active", "plan_id": 1, "end_date": "2025-10-24 00:00:00", "member_id": "3", "created_by": 1, "start_date": "2025-10-24 00:00:00", "updated_by": 1, "subscription_id": 21, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (171, 'pool_schema.subscriptions', '19', 'UPDATE', 1, '2025-10-24 15:16:08.080479+01', '{"status": "paused"}', '{"status": "active"}');
INSERT INTO pool_schema.audit_log VALUES (172, 'pool_schema.subscriptions', '1', 'UPDATE', 1, '2025-10-24 15:16:29.261253+01', '{"status": "paused"}', '{"status": "active"}');
INSERT INTO pool_schema.audit_log VALUES (173, 'pool_schema.payments', '3', 'DELETE', 1, '2025-10-24 15:19:51.674374+01', '{"notes": "tgg", "amount": "4000.00", "payment_id": 3, "payment_date": "2025-10-22 14:46:52.28895+01", "payment_method": "cash", "subscription_id": 1, "received_by_staff_id": 1}', NULL);
INSERT INTO pool_schema.audit_log VALUES (174, 'pool_schema.payments', '2', 'DELETE', 1, '2025-10-24 15:19:56.174895+01', '{"notes": "11", "amount": "3500.00", "payment_id": 2, "payment_date": "2025-10-22 14:42:23.482798+01", "payment_method": "cash", "subscription_id": 2, "received_by_staff_id": 1}', NULL);
INSERT INTO pool_schema.audit_log VALUES (175, 'pool_schema.subscriptions', '1', 'UPDATE', 1, '2025-10-24 16:05:17.932595+01', '{"visits_per_week": 2}', '{"visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (176, 'pool_schema.subscriptions', '22', 'CREATE', 1, '2025-10-24 17:37:39.454325+01', NULL, '{"status": "active", "plan_id": 3, "end_date": "2025-10-31 00:00:00", "member_id": "3", "created_by": 1, "start_date": "2025-10-24 00:00:00", "updated_by": 1, "subscription_id": 22, "visits_per_week": "1"}');
INSERT INTO pool_schema.audit_log VALUES (177, 'pool_schema.subscriptions', '23', 'CREATE', 1, '2025-10-24 17:43:57.989165+01', NULL, '{"status": "active", "plan_id": 3, "end_date": "2025-10-24 00:00:00", "member_id": "2", "created_by": 1, "start_date": "2025-10-24 00:00:00", "updated_by": 1, "subscription_id": 23, "visits_per_week": "1"}');
INSERT INTO pool_schema.audit_log VALUES (178, 'pool_schema.subscriptions', '22', 'UPDATE', 1, '2025-10-24 18:30:42.895273+01', '{"status": "active"}', '{"status": "paused"}');
INSERT INTO pool_schema.audit_log VALUES (179, 'pool_schema.subscriptions', '24', 'CREATE', 1, '2025-10-24 18:31:02.837957+01', NULL, '{"status": "active", "plan_id": 1, "end_date": "2025-10-25 00:00:00", "member_id": "3", "created_by": 1, "start_date": "2025-10-25 00:00:00", "updated_by": 1, "subscription_id": 24, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (180, 'pool_schema.subscriptions', '24', 'UPDATE', 1, '2025-10-24 18:43:23.842941+01', '{"start_date": "2025-10-25T00:00:00.000000Z"}', '{"start_date": "2025-10-24 00:00:00"}');
INSERT INTO pool_schema.audit_log VALUES (181, 'pool_schema.subscriptions', '24', 'UPDATE', 1, '2025-10-24 18:43:51.395872+01', '{"status": "active"}', '{"status": "paused"}');
INSERT INTO pool_schema.audit_log VALUES (182, 'pool_schema.subscriptions', '22', 'UPDATE', 1, '2025-10-24 18:44:06.204686+01', '{"status": "paused"}', '{"status": "active"}');
INSERT INTO pool_schema.audit_log VALUES (183, 'pool_schema.subscriptions', '22', 'UPDATE', 1, '2025-10-24 19:11:31.000266+01', '{"end_date": "2025-10-31T00:00:00.000000Z", "start_date": "2025-10-24T00:00:00.000000Z"}', '{"end_date": "2025-10-23 00:00:00", "start_date": "2025-10-22 00:00:00"}');
INSERT INTO pool_schema.audit_log VALUES (184, 'pool_schema.subscriptions', '22', 'UPDATE', 1, '2025-10-24 20:59:52.886741+01', '{"end_date": "2025-10-23T00:00:00.000000Z"}', '{"end_date": "2025-10-26 00:00:00"}');
INSERT INTO pool_schema.audit_log VALUES (189, 'pool_schema.members', '17', 'CREATE', 1, '2025-10-25 00:13:58.437296+01', NULL, '{"email": "pemoti6884@dxirl.com", "address": "bouira lakhdaria", "last_name": "user30", "member_id": 17, "created_by": 1, "first_name": "u30", "updated_by": 1, "phone_number": "+21310003258", "date_of_birth": "2021-06-25"}');
INSERT INTO pool_schema.audit_log VALUES (190, 'pool_schema.access_badges', '23', 'UPDATE', 1, '2025-10-25 00:13:58.437296+01', '{"status": "inactive", "member_id": null}', '{"status": "active", "member_id": 17}');
INSERT INTO pool_schema.audit_log VALUES (191, 'pool_schema.subscriptions', '25', 'CREATE', 1, '2025-10-25 00:13:58.437296+01', NULL, '{"status": "active", "plan_id": "1", "end_date": "2025-10-25 00:00:00", "member_id": 17, "created_by": 1, "start_date": "2025-10-24 00:00:00", "updated_at": "2025-10-24T23:13:58.480159Z", "updated_by": 1, "subscription_id": 25, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (192, 'pool_schema.access_badges', '1', 'DELETE', 1, '2025-10-25 00:43:44.641396+01', '{"status": "active", "badge_id": 1, "badge_uid": "B-68F92FCC766BC", "issued_at": "2025-10-22 19:26:04+01", "member_id": 5, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (193, 'pool_schema.access_badges', '2', 'DELETE', 1, '2025-10-25 00:43:59.657528+01', '{"status": "active", "badge_id": 2, "badge_uid": "B-68F93720ACF83", "issued_at": "2025-10-22 19:57:20+01", "member_id": 6, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (194, 'pool_schema.members', '18', 'CREATE', 1, '2025-10-25 11:28:55.097094+01', NULL, '{"email": "user40@gmail.com", "address": "bouira lakhdaria", "last_name": "user40", "member_id": 18, "created_by": 1, "first_name": "u40", "updated_by": 1, "phone_number": "+21310003258", "date_of_birth": "2013-06-25"}');
INSERT INTO pool_schema.audit_log VALUES (195, 'pool_schema.access_badges', '32', 'UPDATE', 1, '2025-10-25 11:28:55.097094+01', '{"member_id": null}', '{"member_id": 18}');
INSERT INTO pool_schema.audit_log VALUES (196, 'pool_schema.subscriptions', '26', 'CREATE', 1, '2025-10-25 11:28:55.097094+01', NULL, '{"status": "active", "plan_id": "3", "end_date": "2025-10-26 00:00:00", "member_id": 18, "created_by": 1, "start_date": "2025-10-25 00:00:00", "updated_at": "2025-10-25T10:28:55.365035Z", "updated_by": 1, "subscription_id": 26, "visits_per_week": "1"}');
INSERT INTO pool_schema.audit_log VALUES (197, 'pool_schema.access_badges', '32', 'UPDATE', 1, '2025-10-25 11:31:04.736334+01', '{"status": "inactive"}', '{"status": "active"}');
INSERT INTO pool_schema.audit_log VALUES (198, 'pool_schema.access_badges', '11', 'UPDATE', 1, '2025-10-25 12:18:33.216934+01', '{"status": "active", "member_id": 3}', '{"status": "inactive", "member_id": null}');
INSERT INTO pool_schema.audit_log VALUES (199, 'pool_schema.subscriptions', '24', 'UPDATE', 1, '2025-10-25 12:18:33.216934+01', '{"updated_at": "2025-10-24 23:46:17.487819+01"}', '{"updated_at": "2025-10-25T11:18:33.409187Z"}');
INSERT INTO pool_schema.audit_log VALUES (200, 'pool_schema.subscriptions', '22', 'UPDATE', 1, '2025-10-25 12:18:33.216934+01', '{"updated_at": "2025-10-24 23:46:17.487819+01"}', '{"updated_at": "2025-10-25T11:18:33.414127Z"}');
INSERT INTO pool_schema.audit_log VALUES (202, 'pool_schema.subscriptions', '22', 'UPDATE', 1, '2025-10-25 12:18:47.415801+01', '{"updated_at": "2025-10-25 11:18:33+01"}', '{"updated_at": "2025-10-25T11:18:47.439968Z"}');
INSERT INTO pool_schema.audit_log VALUES (203, 'pool_schema.members', '3', 'UPDATE', 1, '2025-10-25 12:19:14.064411+01', '{"updated_by": null, "phone_number": "65847555", "date_of_birth": null}', '{"updated_by": 1, "phone_number": "+213065847555", "date_of_birth": "1994-11-01"}');
INSERT INTO pool_schema.audit_log VALUES (204, 'pool_schema.subscriptions', '24', 'UPDATE', 1, '2025-10-25 12:19:14.064411+01', '{"updated_at": "2025-10-25 11:18:47+01"}', '{"updated_at": "2025-10-25T11:19:14.085054Z"}');
INSERT INTO pool_schema.audit_log VALUES (205, 'pool_schema.subscriptions', '22', 'UPDATE', 1, '2025-10-25 12:19:14.064411+01', '{"updated_at": "2025-10-25 11:18:47+01"}', '{"updated_at": "2025-10-25T11:19:14.090061Z"}');
INSERT INTO pool_schema.audit_log VALUES (206, 'pool_schema.members', '2', 'UPDATE', 1, '2025-10-25 12:39:39.587254+01', '{"updated_by": null, "date_of_birth": null}', '{"updated_by": 1, "date_of_birth": "2025-10-25 00:00:00"}');
INSERT INTO pool_schema.audit_log VALUES (207, 'pool_schema.subscriptions', '23', 'UPDATE', 1, '2025-10-25 12:39:39.587254+01', '{"updated_at": "2025-10-24 23:46:17.487819+01"}', '{"updated_at": "2025-10-25T11:39:39.607012Z"}');
INSERT INTO pool_schema.audit_log VALUES (208, 'pool_schema.subscriptions', '2', 'UPDATE', 1, '2025-10-25 12:39:39.587254+01', '{"updated_at": "2025-10-24 23:46:17.487819+01"}', '{"updated_at": "2025-10-25T11:39:39.611219Z"}');
INSERT INTO pool_schema.audit_log VALUES (209, 'pool_schema.subscriptions', '24', 'UPDATE', 1, '2025-10-25 12:40:16.833597+01', '{"status": "active", "updated_at": "2025-10-25 11:19:14+01"}', '{"status": "paused", "updated_at": "2025-10-25T11:40:16.848404Z"}');
INSERT INTO pool_schema.audit_log VALUES (210, 'pool_schema.subscriptions', '22', 'UPDATE', 1, '2025-10-25 12:40:16.833597+01', '{"status": "active", "updated_at": "2025-10-25 11:19:14+01"}', '{"status": "paused", "updated_at": "2025-10-25T11:40:16.862231Z"}');
INSERT INTO pool_schema.audit_log VALUES (211, 'pool_schema.members', '3', 'UPDATE', 1, '2025-10-25 12:44:47.77834+01', '{"date_of_birth": "1994-11-01T00:00:00.000000Z"}', '{"date_of_birth": "1994-11-02 00:00:00"}');
INSERT INTO pool_schema.audit_log VALUES (212, 'pool_schema.subscriptions', '24', 'UPDATE', 1, '2025-10-25 12:44:47.77834+01', '{"updated_at": "2025-10-25 11:40:16+01"}', '{"updated_at": "2025-10-25T11:44:47.802544Z"}');
INSERT INTO pool_schema.audit_log VALUES (213, 'pool_schema.subscriptions', '22', 'UPDATE', 1, '2025-10-25 12:44:47.77834+01', '{"updated_at": "2025-10-25 11:40:16+01"}', '{"updated_at": "2025-10-25T11:44:47.808212Z"}');
INSERT INTO pool_schema.audit_log VALUES (214, 'pool_schema.members', '3', 'UPDATE', 1, '2025-10-25 12:45:03.39746+01', '{"date_of_birth": "1994-11-02T00:00:00.000000Z"}', '{"date_of_birth": "1994-11-01 00:00:00"}');
INSERT INTO pool_schema.audit_log VALUES (215, 'pool_schema.subscriptions', '24', 'UPDATE', 1, '2025-10-25 12:45:03.39746+01', '{"status": "paused", "updated_at": "2025-10-25 11:44:47+01"}', '{"status": "active", "updated_at": "2025-10-25T11:45:03.413159Z"}');
INSERT INTO pool_schema.audit_log VALUES (216, 'pool_schema.subscriptions', '22', 'UPDATE', 1, '2025-10-25 12:45:03.39746+01', '{"updated_at": "2025-10-25 11:44:47+01"}', '{"updated_at": "2025-10-25T11:45:03.416827Z"}');
INSERT INTO pool_schema.audit_log VALUES (217, 'pool_schema.members', '3', 'UPDATE', 1, '2025-10-25 12:50:42.39825+01', '{"date_of_birth": "1994-11-01T00:00:00.000000Z"}', '{"date_of_birth": "1994-11-02 00:00:00"}');
INSERT INTO pool_schema.audit_log VALUES (218, 'pool_schema.subscriptions', '24', 'UPDATE', 1, '2025-10-25 12:50:42.39825+01', '{"updated_at": "2025-10-25 11:45:03+01"}', '{"updated_at": "2025-10-25T11:50:42.414683Z"}');
INSERT INTO pool_schema.audit_log VALUES (219, 'pool_schema.subscriptions', '22', 'UPDATE', 1, '2025-10-25 12:50:42.39825+01', '{"updated_at": "2025-10-25 11:45:03+01"}', '{"updated_at": "2025-10-25T11:50:42.418519Z"}');
INSERT INTO pool_schema.audit_log VALUES (220, 'pool_schema.access_badges', '38', 'DELETE', 1, '2025-10-25 12:50:57.262582+01', '{"status": "inactive", "badge_id": 38, "badge_uid": "UID-4007", "issued_at": "2025-10-24 23:39:34.854398+01", "member_id": null, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (221, 'pool_schema.access_badges', '37', 'UPDATE', 1, '2025-10-25 12:53:06.42985+01', '{"status": "inactive"}', '{"status": "active"}');
INSERT INTO pool_schema.audit_log VALUES (222, 'pool_schema.access_badges', '37', 'UPDATE', 1, '2025-10-25 12:53:15.710154+01', '{"status": "active"}', '{"status": "inactive"}');
INSERT INTO pool_schema.audit_log VALUES (223, 'pool_schema.payments', '4', 'CREATE', 1, '2025-10-25 13:24:16.203635+01', NULL, '{"notes": "paye  cach", "amount": "800", "payment_id": 4, "payment_date": "2025-10-25 12:24:16", "payment_method": "cash", "subscription_id": "24", "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (224, 'pool_schema.payments', '5', 'CREATE', 1, '2025-10-25 13:45:17.346326+01', NULL, '{"notes": null, "amount": "3500", "payment_id": 5, "payment_date": "2025-10-25 12:45:17", "payment_method": "cash", "subscription_id": "23", "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (228, 'pool_schema.access_badges', '17', 'UPDATE', 1, '2025-10-25 13:50:38.047726+01', '{"status": "inactive", "member_id": null}', '{"status": "active", "member_id": "2"}');
INSERT INTO pool_schema.audit_log VALUES (231, 'pool_schema.payments', '6', 'CREATE', 1, '2025-10-25 16:34:23.86406+01', NULL, '{"notes": "carte", "amount": "800", "payment_id": 6, "payment_date": "2025-10-25 15:34:23", "payment_method": "card", "subscription_id": "26", "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (232, 'pool_schema.payments', '5', 'UPDATE', 1, '2025-10-25 16:34:37.308723+01', '{"payment_date": "2025-10-25T11:45:17.000000Z"}', '{"payment_date": "2025-10-25 15:34:37"}');
INSERT INTO pool_schema.audit_log VALUES (233, 'pool_schema.subscriptions', '27', 'CREATE', 1, '2025-10-25 16:57:31.310217+01', NULL, '{"status": "active", "plan_id": 1, "end_date": "2025-10-25 00:00:00", "member_id": "3", "created_by": 1, "start_date": "2025-10-25 00:00:00", "updated_at": "2025-10-25T15:57:31.400445Z", "updated_by": 1, "subscription_id": 27, "visits_per_week": "1"}');
INSERT INTO pool_schema.audit_log VALUES (234, 'pool_schema.subscriptions', '27', 'UPDATE', 1, '2025-10-25 16:58:25.315167+01', '{"visits_per_week": 1}', '{"visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (235, 'pool_schema.subscriptions', '28', 'CREATE', 1, '2025-10-25 16:59:14.435357+01', NULL, '{"status": "active", "plan_id": 1, "end_date": "2025-10-26 00:00:00", "member_id": "3", "created_by": 1, "start_date": "2025-10-26 00:00:00", "updated_at": "2025-10-25T15:59:14.438049Z", "updated_by": 1, "subscription_id": 28, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (236, 'pool_schema.subscriptions', '29', 'UPDATE', 1, '2025-10-25 17:12:29.772669+01', '{"updated_at": "2025-10-25T15:04:49.000000Z", "updated_by": null}', '{"updated_at": "2025-10-25 16:12:29", "updated_by": 1}');
INSERT INTO pool_schema.audit_log VALUES (288, 'pool_schema.subscriptions', '27', 'UPDATE', 1, '2025-10-26 01:47:16.878773+01', '{"updated_at": "2025-10-25T23:32:26.000000Z"}', '{"updated_at": "2025-10-26 00:47:16"}');
INSERT INTO pool_schema.audit_log VALUES (237, 'pool_schema.payments', '8', 'DELETE', 1, '2025-10-25 17:25:02.926779+01', '{"notes": null, "amount": "900.00", "payment_id": 8, "payment_date": "2025-10-25 16:12:29+01", "payment_method": "cash", "subscription_id": 29, "received_by_staff_id": 1}', NULL);
INSERT INTO pool_schema.audit_log VALUES (238, 'pool_schema.payments', '10', 'CREATE', 1, '2025-10-25 18:43:31.219363+01', NULL, '{"notes": null, "amount": "2102", "payment_id": 10, "payment_date": "2025-10-25 17:43:31", "payment_method": "cash", "subscription_id": "26", "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (239, 'pool_schema.payments', '10', 'DELETE', 1, '2025-10-25 18:43:56.239408+01', '{"notes": null, "amount": "2102.00", "payment_id": 10, "payment_date": "2025-10-25 17:43:31+01", "payment_method": "cash", "subscription_id": 26, "received_by_staff_id": 1}', NULL);
INSERT INTO pool_schema.audit_log VALUES (243, 'pool_schema.access_badges', '11', 'UPDATE', 1, '2025-10-25 22:55:47.965578+01', '{"status": "inactive", "issued_at": "2025-10-23 18:25:00+01", "member_id": null}', '{"status": "active", "issued_at": "2025-10-25T21:55:47.979222Z", "member_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (244, 'pool_schema.access_badges', NULL, 'badge_changed', 1, '2025-10-25 21:55:47+01', '{"old_badge_uid": "Aucun"}', '{"new_badge_uid": ""}');
INSERT INTO pool_schema.audit_log VALUES (245, 'pool_schema.access_badges', '14', 'UPDATE', 1, '2025-10-25 22:56:09.787844+01', '{"status": "inactive", "issued_at": "2025-10-24 23:39:34.854398+01", "member_id": null}', '{"status": "active", "issued_at": "2025-10-25T21:56:09.796427Z", "member_id": 4}');
INSERT INTO pool_schema.audit_log VALUES (246, 'pool_schema.access_badges', NULL, 'badge_changed', 1, '2025-10-25 21:56:09+01', '{"old_badge_uid": "Aucun"}', '{"new_badge_uid": ""}');
INSERT INTO pool_schema.audit_log VALUES (247, 'pool_schema.subscriptions', '5', 'UPDATE', 1, '2025-10-25 22:56:09.787844+01', '{"updated_at": "2025-10-24T22:46:17.487819Z"}', '{"updated_at": "2025-10-25 21:56:09"}');
INSERT INTO pool_schema.audit_log VALUES (248, 'pool_schema.access_badges', '10', 'UPDATE', 1, '2025-10-25 23:01:34.003569+01', '{"status": "active", "member_id": 14}', '{"status": "inactive", "member_id": null}');
INSERT INTO pool_schema.audit_log VALUES (249, 'pool_schema.access_badges', '15', 'UPDATE', 1, '2025-10-25 23:01:34.003569+01', '{"status": "inactive", "issued_at": "2025-10-24 23:39:34.854398+01", "member_id": null}', '{"status": "active", "issued_at": "2025-10-25T22:01:34.018281Z", "member_id": 14}');
INSERT INTO pool_schema.audit_log VALUES (250, 'pool_schema.access_badges', NULL, 'badge_changed', 1, '2025-10-25 22:01:34+01', '{"old_badge_uid": {"status": "inactive", "badge_id": 10, "badge_uid": "B-68FA45C356A1B", "issued_at": "2025-10-23 15:12:03+01", "member_id": null, "expires_at": null}}', '{"new_badge_uid": ""}');
INSERT INTO pool_schema.audit_log VALUES (251, 'pool_schema.access_badges', '11', 'UPDATE', 1, '2025-10-25 23:06:19.609574+01', '{"status": "active", "member_id": 1}', '{"status": "inactive", "member_id": null}');
INSERT INTO pool_schema.audit_log VALUES (252, 'pool_schema.access_badges', '37', 'UPDATE', 1, '2025-10-25 23:06:19.609574+01', '{"status": "inactive", "issued_at": "2025-10-24 23:39:34.854398+01", "member_id": null}', '{"status": "active", "issued_at": "2025-10-25T22:06:19.636352Z", "member_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (253, 'pool_schema.access_badges', NULL, 'badge_changed', 1, '2025-10-25 22:06:19+01', '{"old_badge_uid": {"status": "inactive", "badge_id": 11, "badge_uid": "B-68FA72FC5164C", "issued_at": "2025-10-25 21:55:47+01", "member_id": null, "expires_at": null}}', '{"new_badge_uid": {"status": "active", "badge_id": 37, "badge_uid": "UID-4006", "issued_at": "2025-10-25T22:06:19.636352Z", "member_id": 1, "expires_at": null}}');
INSERT INTO pool_schema.audit_log VALUES (254, 'pool_schema.members', '1', 'UPDATE', NULL, '2025-10-25 23:40:22.654145+01', '{"email": "kohil.abdelhak10@gmail.com", "address": "16000", "last_name": "m", "member_id": 1, "created_at": "2025-10-22T01:14:10.659291+01:00", "created_by": null, "first_name": "member1", "updated_by": null, "phone_number": "+21310003258", "date_of_birth": "2025-10-23"}', '{"email": "kohil.abdelhak10@gmail.com", "address": "16000", "last_name": "m", "member_id": 1, "created_at": "2025-10-22T01:14:10.659291+01:00", "created_by": null, "first_name": "member1", "updated_by": null, "phone_number": "0777777777", "date_of_birth": "2025-10-23"}');
INSERT INTO pool_schema.audit_log VALUES (255, 'pool_schema.members', '21', 'INSERT', NULL, '2025-10-25 23:41:19.055895+01', NULL, '{"email": "user50@gmail.com", "address": "bouira lakhdaria", "last_name": "user50", "member_id": 21, "created_at": "2025-10-25T23:41:19.055895+01:00", "created_by": 1, "first_name": "u50", "updated_at": "2025-10-25T23:41:19.055895+01:00", "updated_by": 1, "phone_number": "03215478", "date_of_birth": "2025-10-25"}');
INSERT INTO pool_schema.audit_log VALUES (256, 'pool_schema.members', '21', 'CREATE', 1, '2025-10-25 23:41:19.055895+01', NULL, '{"email": "user50@gmail.com", "address": "bouira lakhdaria", "last_name": "user50", "member_id": 21, "created_by": 1, "first_name": "u50", "updated_by": 1, "phone_number": "03215478", "date_of_birth": "2025-10-25 00:00:00"}');
INSERT INTO pool_schema.audit_log VALUES (257, 'pool_schema.access_badges', '21', 'UPDATE', NULL, '2025-10-25 23:41:19.055895+01', '{"status": "inactive", "badge_id": 10, "badge_uid": "B-68FA45C356A1B", "issued_at": "2025-10-23T15:12:03+01:00", "member_id": null, "expires_at": null}', '{"status": "active", "badge_id": 10, "badge_uid": "B-68FA45C356A1B", "issued_at": "2025-10-23T15:12:03+01:00", "member_id": 21, "expires_at": null}');
INSERT INTO pool_schema.audit_log VALUES (258, 'pool_schema.access_badges', '10', 'UPDATE', 1, '2025-10-25 23:41:19.055895+01', '{"status": "inactive", "member_id": null}', '{"status": "active", "member_id": 21}');
INSERT INTO pool_schema.audit_log VALUES (259, 'pool_schema.subscriptions', '21', 'INSERT', NULL, '2025-10-25 23:41:19.055895+01', NULL, '{"status": "active", "plan_id": 1, "end_date": "2025-10-25", "member_id": 21, "paused_at": null, "created_at": "2025-10-25T23:41:19.055895+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_at": "2025-10-25T22:41:19+01:00", "updated_by": 1, "deactivated_by": null, "subscription_id": 30, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (260, 'pool_schema.subscriptions', '30', 'CREATE', 1, '2025-10-25 23:41:19.055895+01', NULL, '{"status": "active", "plan_id": "1", "end_date": "2025-10-25 00:00:00", "member_id": 21, "created_by": 1, "start_date": "2025-10-25 00:00:00", "updated_at": "2025-10-25 22:41:19", "updated_by": 1, "subscription_id": 30, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (261, 'pool_schema.members', '22', 'INSERT', NULL, '2025-10-25 23:51:49.784514+01', NULL, '{"email": "user60@gmail.com", "address": "bouira lakhdaria", "last_name": "user60", "member_id": 22, "created_at": "2025-10-25T23:51:49.784514+01:00", "created_by": 1, "first_name": "u60", "updated_at": "2025-10-25T23:51:49.784514+01:00", "updated_by": 1, "phone_number": "+213065847555", "date_of_birth": "2025-10-25"}');
INSERT INTO pool_schema.audit_log VALUES (289, 'pool_schema.subscriptions', '29', 'UPDATE', 1, '2025-10-26 01:47:16.878773+01', '{"updated_at": "2025-10-25T23:32:26.000000Z"}', '{"updated_at": "2025-10-26 00:47:16"}');
INSERT INTO pool_schema.audit_log VALUES (262, 'pool_schema.members', '22', 'CREATE', 1, '2025-10-25 23:51:49.784514+01', NULL, '{"email": "user60@gmail.com", "address": "bouira lakhdaria", "last_name": "user60", "member_id": 22, "created_by": 1, "first_name": "u60", "updated_by": 1, "phone_number": "+213065847555", "date_of_birth": "2025-10-25 00:00:00"}');
INSERT INTO pool_schema.audit_log VALUES (263, 'pool_schema.access_badges', '22', 'UPDATE', NULL, '2025-10-25 23:51:49.784514+01', '{"status": "inactive", "badge_id": 11, "badge_uid": "B-68FA72FC5164C", "issued_at": "2025-10-25T21:55:47+01:00", "member_id": null, "expires_at": null}', '{"status": "active", "badge_id": 11, "badge_uid": "B-68FA72FC5164C", "issued_at": "2025-10-25T21:55:47+01:00", "member_id": 22, "expires_at": null}');
INSERT INTO pool_schema.audit_log VALUES (264, 'pool_schema.access_badges', '11', 'UPDATE', 1, '2025-10-25 23:51:49.784514+01', '{"status": "inactive", "member_id": null}', '{"status": "active", "member_id": 22}');
INSERT INTO pool_schema.audit_log VALUES (265, 'pool_schema.subscriptions', '22', 'INSERT', NULL, '2025-10-25 23:51:49.784514+01', NULL, '{"status": "active", "plan_id": 1, "end_date": "2025-10-25", "member_id": 22, "paused_at": null, "created_at": "2025-10-25T23:51:49.784514+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_at": "2025-10-25T22:51:49+01:00", "updated_by": 1, "deactivated_by": null, "subscription_id": 31, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (266, 'pool_schema.subscriptions', '31', 'CREATE', 1, '2025-10-25 23:51:49.784514+01', NULL, '{"status": "active", "plan_id": "1", "end_date": "2025-10-25 00:00:00", "member_id": 22, "created_by": 1, "start_date": "2025-10-25 00:00:00", "updated_at": "2025-10-25 22:51:49", "updated_by": 1, "subscription_id": 31, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (267, 'pool_schema.members', '23', 'INSERT', NULL, '2025-10-25 23:54:38.706317+01', NULL, '{"email": "user70@gmail.com", "address": "bouira lakhdaria", "last_name": "user70", "member_id": 23, "created_at": "2025-10-25T23:54:38.706317+01:00", "created_by": 1, "first_name": "u70", "updated_at": "2025-10-25T23:54:38.706317+01:00", "updated_by": 1, "phone_number": "+21310003258", "date_of_birth": "2025-10-25"}');
INSERT INTO pool_schema.audit_log VALUES (268, 'pool_schema.members', '23', 'CREATE', 1, '2025-10-25 23:54:38.706317+01', NULL, '{"email": "user70@gmail.com", "address": "bouira lakhdaria", "last_name": "user70", "member_id": 23, "created_by": 1, "first_name": "u70", "updated_by": 1, "phone_number": "+21310003258", "date_of_birth": "2025-10-25 00:00:00"}');
INSERT INTO pool_schema.audit_log VALUES (269, 'pool_schema.access_badges', '23', 'UPDATE', NULL, '2025-10-25 23:54:38.706317+01', '{"status": "inactive", "badge_id": 36, "badge_uid": "UID-4005", "issued_at": "2025-10-24T23:39:34.854398+01:00", "member_id": null, "expires_at": null}', '{"status": "active", "badge_id": 36, "badge_uid": "UID-4005", "issued_at": "2025-10-24T23:39:34.854398+01:00", "member_id": 23, "expires_at": null}');
INSERT INTO pool_schema.audit_log VALUES (270, 'pool_schema.access_badges', '36', 'UPDATE', 1, '2025-10-25 23:54:38.706317+01', '{"status": "inactive", "member_id": null}', '{"status": "active", "member_id": 23}');
INSERT INTO pool_schema.audit_log VALUES (271, 'pool_schema.subscriptions', '23', 'INSERT', NULL, '2025-10-25 23:54:38.706317+01', NULL, '{"status": "active", "plan_id": 1, "end_date": "2025-10-25", "member_id": 23, "paused_at": null, "created_at": "2025-10-25T23:54:38.706317+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_at": "2025-10-25T22:54:38+01:00", "updated_by": 1, "deactivated_by": null, "subscription_id": 32, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (272, 'pool_schema.subscriptions', '32', 'CREATE', 1, '2025-10-25 23:54:38.706317+01', NULL, '{"status": "active", "plan_id": "1", "end_date": "2025-10-25 00:00:00", "member_id": 23, "created_by": 1, "start_date": "2025-10-25 00:00:00", "updated_at": "2025-10-25 22:54:38", "updated_by": 1, "subscription_id": 32, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (273, 'pool_schema.members', '24', 'CREATE', 1, '2025-10-25 23:57:05.007975+01', NULL, '{"email": "user90@gmail.com", "address": "bouira lakhdaria", "last_name": "user90", "member_id": 24, "created_by": 1, "first_name": "u90", "updated_by": 1, "phone_number": "+213065847555", "date_of_birth": "2025-10-25 00:00:00"}');
INSERT INTO pool_schema.audit_log VALUES (274, 'pool_schema.access_badges', '30', 'UPDATE', 1, '2025-10-25 23:57:05.007975+01', '{"status": "inactive", "member_id": null}', '{"status": "active", "member_id": 24}');
INSERT INTO pool_schema.audit_log VALUES (275, 'pool_schema.subscriptions', '33', 'CREATE', 1, '2025-10-25 23:57:05.007975+01', NULL, '{"status": "active", "plan_id": "2", "end_date": "2025-11-25 00:00:00", "member_id": 24, "created_by": 1, "start_date": "2025-10-25 00:00:00", "updated_at": "2025-10-25 22:57:05", "updated_by": 1, "subscription_id": 33, "visits_per_week": "2"}');
INSERT INTO pool_schema.audit_log VALUES (276, 'pool_schema.access_badges', '30', 'DELETE', 1, '2025-10-26 00:04:45.005617+01', '{"status": "active", "badge_id": 30, "badge_uid": "UID-3008", "issued_at": "2025-10-24 23:39:34.854398+01", "member_id": 24, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (277, 'pool_schema.subscriptions', '33', 'DELETE', 1, '2025-10-26 00:04:45.01858+01', '{"status": "active", "plan_id": 2, "end_date": "2025-11-25", "member_id": 24, "paused_at": null, "created_at": "2025-10-25 23:57:05.007975+01", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_at": "2025-10-25 22:57:05+01", "updated_by": 1, "deactivated_by": null, "subscription_id": 33, "visits_per_week": 2}', NULL);
INSERT INTO pool_schema.audit_log VALUES (278, 'pool_schema.members', '24', 'DELETE', 1, '2025-10-26 00:04:45.021643+01', '{"email": "user90@gmail.com", "address": "bouira lakhdaria", "last_name": "user90", "member_id": 24, "created_at": "2025-10-25 23:57:05.007975+01", "created_by": 1, "first_name": "u90", "updated_at": "2025-10-25 23:57:05.007975+01", "updated_by": 1, "phone_number": "+213065847555", "date_of_birth": "2025-10-25"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (279, 'pool_schema.subscriptions', '24', 'UPDATE', 1, '2025-10-26 01:32:26.649119+01', '{"status": "active", "updated_at": "2025-10-25T10:50:42.000000Z"}', '{"status": "paused", "updated_at": "2025-10-26 00:32:26"}');
INSERT INTO pool_schema.audit_log VALUES (280, 'pool_schema.subscriptions', '22', 'UPDATE', 1, '2025-10-26 01:32:26.649119+01', '{"updated_at": "2025-10-25T10:50:42.000000Z"}', '{"updated_at": "2025-10-26 00:32:26"}');
INSERT INTO pool_schema.audit_log VALUES (281, 'pool_schema.subscriptions', '27', 'UPDATE', 1, '2025-10-26 01:32:26.649119+01', '{"updated_at": "2025-10-25T14:57:31.000000Z"}', '{"updated_at": "2025-10-26 00:32:26"}');
INSERT INTO pool_schema.audit_log VALUES (282, 'pool_schema.subscriptions', '29', 'UPDATE', 1, '2025-10-26 01:32:26.649119+01', '{"updated_at": "2025-10-25T18:09:10.000000Z", "updated_by": null}', '{"updated_at": "2025-10-26 00:32:26", "updated_by": 1}');
INSERT INTO pool_schema.audit_log VALUES (283, 'pool_schema.subscriptions', '28', 'UPDATE', 1, '2025-10-26 01:32:26.649119+01', '{"updated_at": "2025-10-25T18:39:17.000000Z", "updated_by": null}', '{"updated_at": "2025-10-26 00:32:26", "updated_by": 1}');
INSERT INTO pool_schema.audit_log VALUES (284, 'pool_schema.subscriptions', '34', 'UPDATE', 1, '2025-10-26 01:32:26.649119+01', '{"updated_at": "2025-10-25T23:31:16.000000Z", "updated_by": null}', '{"updated_at": "2025-10-26 00:32:26", "updated_by": 1}');
INSERT INTO pool_schema.audit_log VALUES (285, 'pool_schema.subscriptions', '23', 'UPDATE', 1, '2025-10-26 01:45:25.220518+01', '{"status": "active", "updated_at": "2025-10-25T10:39:39.000000Z"}', '{"status": "paused", "updated_at": "2025-10-26 00:45:25"}');
INSERT INTO pool_schema.audit_log VALUES (286, 'pool_schema.subscriptions', '2', 'UPDATE', 1, '2025-10-26 01:45:25.220518+01', '{"updated_at": "2025-10-25T10:39:39.000000Z"}', '{"updated_at": "2025-10-26 00:45:25"}');
INSERT INTO pool_schema.audit_log VALUES (287, 'pool_schema.subscriptions', '22', 'UPDATE', 1, '2025-10-26 01:47:16.878773+01', '{"status": "paused", "updated_at": "2025-10-25T23:32:26.000000Z"}', '{"status": "expired", "updated_at": "2025-10-26 00:47:16"}');
INSERT INTO pool_schema.audit_log VALUES (290, 'pool_schema.subscriptions', '28', 'UPDATE', 1, '2025-10-26 01:47:16.878773+01', '{"updated_at": "2025-10-25T23:32:26.000000Z"}', '{"updated_at": "2025-10-26 00:47:16"}');
INSERT INTO pool_schema.audit_log VALUES (291, 'pool_schema.subscriptions', '34', 'UPDATE', 1, '2025-10-26 01:47:16.878773+01', '{"updated_at": "2025-10-25T23:44:02.000000Z", "updated_by": null}', '{"updated_at": "2025-10-26 00:47:16", "updated_by": 1}');
INSERT INTO pool_schema.audit_log VALUES (292, 'pool_schema.subscriptions', '24', 'UPDATE', 1, '2025-10-26 01:47:16.878773+01', '{"updated_at": "2025-10-25T23:32:26.000000Z"}', '{"updated_at": "2025-10-26 00:47:16"}');
INSERT INTO pool_schema.audit_log VALUES (293, 'pool_schema.members', '3', 'UPDATE', 1, '2025-10-26 01:49:20.134393+01', '{"last_name": "Ka"}', '{"last_name": "Kohil"}');
INSERT INTO pool_schema.audit_log VALUES (294, 'pool_schema.subscriptions', '22', 'UPDATE', 1, '2025-10-26 01:49:20.134393+01', '{"updated_at": "2025-10-25T23:47:16.000000Z"}', '{"updated_at": "2025-10-26 00:49:20"}');
INSERT INTO pool_schema.audit_log VALUES (295, 'pool_schema.subscriptions', '27', 'UPDATE', 1, '2025-10-26 01:49:20.134393+01', '{"updated_at": "2025-10-25T23:47:16.000000Z"}', '{"updated_at": "2025-10-26 00:49:20"}');
INSERT INTO pool_schema.audit_log VALUES (296, 'pool_schema.subscriptions', '29', 'UPDATE', 1, '2025-10-26 01:49:20.134393+01', '{"updated_at": "2025-10-25T23:47:16.000000Z"}', '{"updated_at": "2025-10-26 00:49:20"}');
INSERT INTO pool_schema.audit_log VALUES (297, 'pool_schema.subscriptions', '28', 'UPDATE', 1, '2025-10-26 01:49:20.134393+01', '{"updated_at": "2025-10-25T23:47:16.000000Z"}', '{"updated_at": "2025-10-26 00:49:20"}');
INSERT INTO pool_schema.audit_log VALUES (298, 'pool_schema.subscriptions', '34', 'UPDATE', 1, '2025-10-26 01:49:20.134393+01', '{"updated_at": "2025-10-25T23:47:16.000000Z"}', '{"updated_at": "2025-10-26 00:49:20"}');
INSERT INTO pool_schema.audit_log VALUES (299, 'pool_schema.subscriptions', '24', 'UPDATE', 1, '2025-10-26 01:49:20.134393+01', '{"updated_at": "2025-10-25T23:47:16.000000Z"}', '{"updated_at": "2025-10-26 00:49:20"}');
INSERT INTO pool_schema.audit_log VALUES (300, 'pool_schema.members', '26', 'CREATE', 1, '2025-10-26 01:54:04.287016+01', NULL, '{"email": "abdoaaaa@gmail.com", "address": "bouira lakhdaria", "last_name": "aa", "member_id": 26, "created_by": 1, "first_name": "aa", "updated_by": 1, "phone_number": "02154785", "date_of_birth": "2025-10-26 00:00:00"}');
INSERT INTO pool_schema.audit_log VALUES (301, 'pool_schema.access_badges', '33', 'UPDATE', 1, '2025-10-26 01:54:04.287016+01', '{"status": "inactive", "member_id": null}', '{"status": "active", "member_id": 26}');
INSERT INTO pool_schema.audit_log VALUES (302, 'pool_schema.subscriptions', '35', 'CREATE', 1, '2025-10-26 01:54:04.287016+01', NULL, '{"status": "active", "plan_id": "1", "end_date": "2025-10-26 00:00:00", "member_id": 26, "created_by": 1, "start_date": "2025-10-26 00:00:00", "updated_at": "2025-10-26 00:54:04", "updated_by": 1, "subscription_id": 35, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (303, 'pool_schema.members', '27', 'CREATE', 1, '2025-10-26 02:03:23.311761+01', NULL, '{"email": "abdoqqqq@gmail.com", "address": "16000", "last_name": "qq", "member_id": 27, "created_by": 1, "first_name": "qq", "updated_by": 1, "phone_number": "+213065847555", "date_of_birth": "2025-10-26 00:00:00"}');
INSERT INTO pool_schema.audit_log VALUES (304, 'pool_schema.access_badges', '35', 'UPDATE', 1, '2025-10-26 02:03:23.311761+01', '{"status": "inactive", "member_id": null}', '{"status": "active", "member_id": 27}');
INSERT INTO pool_schema.audit_log VALUES (305, 'pool_schema.subscriptions', '37', 'CREATE', 1, '2025-10-26 02:03:23.311761+01', NULL, '{"status": "active", "plan_id": "1", "end_date": "2025-10-26 00:00:00", "member_id": 27, "created_by": 1, "start_date": "2025-10-26 00:00:00", "updated_at": "2025-10-26 01:03:23", "updated_by": 1, "subscription_id": 37, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (306, 'pool_schema.access_badges', '33', 'DELETE', 1, '2025-10-26 02:03:52.640944+01', '{"status": "active", "badge_id": 33, "badge_uid": "UID-4002", "issued_at": "2025-10-24 23:39:34.854398+01", "member_id": 26, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (307, 'pool_schema.subscriptions', '35', 'DELETE', 1, '2025-10-26 02:03:52.65698+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-26", "member_id": 26, "paused_at": null, "created_at": "2025-10-26 01:54:04.287016+01", "created_by": 1, "resumes_at": null, "start_date": "2025-10-26", "updated_at": "2025-10-26 00:54:04+01", "updated_by": 1, "deactivated_by": null, "subscription_id": 35, "visits_per_week": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (308, 'pool_schema.members', '26', 'DELETE', 1, '2025-10-26 02:03:52.661998+01', '{"email": "abdoaaaa@gmail.com", "address": "bouira lakhdaria", "last_name": "aa", "member_id": 26, "created_at": "2025-10-26 01:54:04.287016+01", "created_by": 1, "first_name": "aa", "updated_at": "2025-10-26 01:54:04.287016+01", "updated_by": 1, "phone_number": "02154785", "date_of_birth": "2025-10-26"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (309, 'pool_schema.members', '28', 'CREATE', 1, '2025-10-26 02:06:01.367816+01', NULL, '{"email": "user30ddddddd@gmail.com", "address": "bouira lakhdaria", "last_name": "ddddddd", "member_id": 28, "created_by": 1, "first_name": "ddddddd", "updated_by": 1, "phone_number": "02154785", "date_of_birth": "2025-10-26 00:00:00"}');
INSERT INTO pool_schema.audit_log VALUES (310, 'pool_schema.access_badges', '31', 'UPDATE', 1, '2025-10-26 02:06:01.367816+01', '{"status": "inactive", "member_id": null}', '{"status": "active", "member_id": 28}');
INSERT INTO pool_schema.audit_log VALUES (311, 'pool_schema.subscriptions', '38', 'CREATE', 1, '2025-10-26 02:06:01.367816+01', NULL, '{"status": "active", "plan_id": "2", "end_date": "2025-11-02 00:00:00", "member_id": 28, "created_by": 1, "start_date": "2025-10-26 00:00:00", "updated_at": "2025-10-26 01:06:01", "updated_by": 1, "subscription_id": 38, "visits_per_week": "2"}');
INSERT INTO pool_schema.audit_log VALUES (312, 'pool_schema.members', '29', 'CREATE', 1, '2025-10-26 02:08:03.761257+01', NULL, '{"email": "abdo.coins10fff@gmail.com", "address": "bouira lakhdaria", "last_name": "ff", "member_id": 29, "created_by": 1, "first_name": "fffffff", "updated_by": 1, "phone_number": "03215478", "date_of_birth": "2025-10-18 00:00:00"}');
INSERT INTO pool_schema.audit_log VALUES (313, 'pool_schema.access_badges', '29', 'UPDATE', 1, '2025-10-26 02:08:03.761257+01', '{"status": "inactive", "member_id": null}', '{"status": "active", "member_id": 29}');
INSERT INTO pool_schema.audit_log VALUES (314, 'pool_schema.subscriptions', '39', 'CREATE', 1, '2025-10-26 02:08:03.761257+01', NULL, '{"status": "active", "plan_id": "3", "end_date": "2025-10-26 00:00:00", "member_id": 29, "created_by": 1, "start_date": "2025-10-26 00:00:00", "updated_at": "2025-10-26 01:08:03", "updated_by": 1, "subscription_id": 39, "visits_per_week": "1"}');
INSERT INTO pool_schema.audit_log VALUES (315, 'pool_schema.access_badges', '31', 'DELETE', 1, '2025-10-26 02:11:44.480917+01', '{"status": "active", "badge_id": 31, "badge_uid": "UID-3009", "issued_at": "2025-10-24 23:39:34.854398+01", "member_id": 28, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (316, 'pool_schema.staff', '11', 'DELETE', 1, '2025-10-26 02:15:21.068328+01', '{"role_id": 5, "staff_id": 11, "username": "user1", "is_active": false, "last_name": "user", "created_at": "2025-10-21 22:22:19+01", "first_name": "user", "password_hash": "$2y$12$W58SiunBh/18ygEfB.hZtuLa50OsSf3xdWieTnE5j9i8qWliifqZq"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (317, 'pool_schema.permissions', '1', 'DELETE', 1, '2025-10-26 02:15:55.994652+01', '{"permission_id": 1, "permission_name": "permis1"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (318, 'pool_schema.permissions', '4', 'CREATE', 1, '2025-10-26 02:16:13.807861+01', NULL, '{"permission_id": 4, "permission_name": "gggggggggggggggg"}');
INSERT INTO pool_schema.audit_log VALUES (319, 'pool_schema.roles', '7', 'CREATE', 1, '2025-10-26 02:17:00.936153+01', NULL, '{"role_id": 7, "role_name": "eeeeeeeeeeeeeeeeeeeeeeeee"}');
INSERT INTO pool_schema.audit_log VALUES (320, 'pool_schema.roles', '7', 'DELETE', 1, '2025-10-26 02:17:04.619833+01', '{"role_id": 7, "role_name": "eeeeeeeeeeeeeeeeeeeeeeeee"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (321, 'pool_schema.members', '28', 'DELETE', NULL, '2025-10-26 02:58:06.482981+01', '{"email": "user30ddddddd@gmail.com", "address": "bouira lakhdaria", "last_name": "ddddddd", "member_id": 28, "created_at": "2025-10-26T02:06:01.367816+01:00", "created_by": 1, "first_name": "ddddddd", "updated_at": "2025-10-26T02:06:01.367816+01:00", "updated_by": 1, "phone_number": "02154785", "date_of_birth": "2025-10-26"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (322, 'pool_schema.subscriptions', '27', 'DELETE', 1, '2025-10-26 03:20:02.323888+01', '{"status": "paused", "plan_id": 1, "end_date": "2025-10-26", "member_id": 27, "paused_at": null, "created_at": "2025-10-26T02:03:23.311761+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-26", "updated_at": "2025-10-26T01:13:46+01:00", "updated_by": null, "deactivated_by": null, "subscription_id": 37, "visits_per_week": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (323, 'pool_schema.access_badges', '27', 'DELETE', 1, '2025-10-26 03:20:38.565562+01', '{"status": "active", "badge_id": 35, "badge_uid": "UID-4004", "issued_at": "2025-10-24T23:39:34.854398+01:00", "member_id": 27, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (324, 'pool_schema.access_badges', '35', 'DELETE', 1, '2025-10-26 03:20:38.577889+01', '{"status": "active", "badge_id": 35, "badge_uid": "UID-4004", "issued_at": "2025-10-24 23:39:34.854398+01", "member_id": 27, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (325, 'pool_schema.access_badges', '29', 'DELETE', 1, '2025-10-26 03:21:19.000082+01', '{"status": "active", "badge_id": 29, "badge_uid": "UID-3007", "issued_at": "2025-10-24T23:39:34.854398+01:00", "member_id": 29, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (326, 'pool_schema.access_badges', '29', 'DELETE', 1, '2025-10-26 03:21:19.009377+01', '{"status": "active", "badge_id": 29, "badge_uid": "UID-3007", "issued_at": "2025-10-24 23:39:34.854398+01", "member_id": 29, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (327, 'pool_schema.members', '29', 'UPDATE', 1, '2025-10-26 03:24:02.733376+01', '{"email": "abdo.coins10fff@gmail.com", "address": "bouira lakhdaria", "last_name": "ff", "member_id": 29, "created_at": "2025-10-26T02:08:03.761257+01:00", "created_by": 1, "first_name": "fffffff", "updated_by": 1, "phone_number": "03215478", "date_of_birth": "2025-10-18"}', '{"email": "abdo.coins10fff@gmail.com", "address": "bouira lakhdaria", "last_name": "ff", "member_id": 29, "created_at": "2025-10-26T02:08:03.761257+01:00", "created_by": 1, "first_name": "fffffff", "updated_by": null, "phone_number": "03215478", "date_of_birth": "2025-10-18"}');
INSERT INTO pool_schema.audit_log VALUES (328, 'pool_schema.access_badges', '29', 'UPDATE', 1, '2025-10-26 03:24:02.733376+01', '{"status": "inactive", "badge_id": 18, "badge_uid": "UID-2001", "issued_at": "2025-10-24T23:39:34.854398+01:00", "member_id": null, "expires_at": null}', '{"status": "active", "badge_id": 18, "badge_uid": "UID-2001", "issued_at": "2025-10-26T02:24:02+01:00", "member_id": 29, "expires_at": null}');
INSERT INTO pool_schema.audit_log VALUES (329, 'pool_schema.access_badges', '18', 'UPDATE', 1, '2025-10-26 03:24:02.733376+01', '{"status": "inactive", "issued_at": "2025-10-24 23:39:34.854398+01", "member_id": null}', '{"status": "active", "issued_at": "2025-10-26T02:24:02.753911Z", "member_id": 29}');
INSERT INTO pool_schema.audit_log VALUES (330, 'pool_schema.payments', '36', 'INSERT', 1, '2025-10-26 03:29:41.647151+01', NULL, '{"notes": null, "amount": 1000.00, "payment_id": 20, "payment_date": "2025-10-26T02:29:41+01:00", "payment_method": "cash", "subscription_id": 36, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (331, 'pool_schema.subscription_allowed_days', '36', 'DELETE', 1, '2025-10-26 03:29:47.818913+01', '{"weekday_id": 1, "subscription_id": 36}', NULL);
INSERT INTO pool_schema.audit_log VALUES (332, 'pool_schema.subscription_allowed_days', '36', 'INSERT', 1, '2025-10-26 03:29:47.822295+01', NULL, '{"weekday_id": 1, "subscription_id": 36}');
INSERT INTO pool_schema.audit_log VALUES (333, 'pool_schema.members', '3', 'UPDATE', 1, '2025-10-26 03:31:44.725647+01', '{"email": "abdo@gmail.com", "address": "16000", "last_name": "Kohil", "member_id": 3, "created_at": "2025-10-22T14:09:21.00445+01:00", "created_by": null, "first_name": "Abdo", "updated_by": 1, "phone_number": "+213065847555", "date_of_birth": "1994-11-02"}', '{"email": "abdo@gmail.com", "address": "16000", "last_name": "Kohil", "member_id": 3, "created_at": "2025-10-22T14:09:21.00445+01:00", "created_by": null, "first_name": "Abd elhak", "updated_by": null, "phone_number": "+213065847555", "date_of_birth": "1994-11-02"}');
INSERT INTO pool_schema.audit_log VALUES (334, 'pool_schema.subscriptions', '22', 'UPDATE', 1, '2025-10-26 03:31:44.725647+01', '{"updated_at": "2025-10-25T23:49:20.000000Z"}', '{"updated_at": "2025-10-26 02:31:44"}');
INSERT INTO pool_schema.audit_log VALUES (335, 'pool_schema.subscriptions', '27', 'UPDATE', 1, '2025-10-26 03:31:44.725647+01', '{"updated_at": "2025-10-25T23:49:20.000000Z"}', '{"updated_at": "2025-10-26 02:31:44"}');
INSERT INTO pool_schema.audit_log VALUES (336, 'pool_schema.subscriptions', '29', 'UPDATE', 1, '2025-10-26 03:31:44.725647+01', '{"updated_at": "2025-10-25T23:49:20.000000Z"}', '{"updated_at": "2025-10-26 02:31:45"}');
INSERT INTO pool_schema.audit_log VALUES (337, 'pool_schema.subscriptions', '28', 'UPDATE', 1, '2025-10-26 03:31:44.725647+01', '{"updated_at": "2025-10-25T23:49:20.000000Z"}', '{"updated_at": "2025-10-26 02:31:45"}');
INSERT INTO pool_schema.audit_log VALUES (338, 'pool_schema.subscriptions', '34', 'UPDATE', 1, '2025-10-26 03:31:44.725647+01', '{"updated_at": "2025-10-25T23:49:20.000000Z"}', '{"updated_at": "2025-10-26 02:31:45"}');
INSERT INTO pool_schema.audit_log VALUES (339, 'pool_schema.subscriptions', '24', 'UPDATE', 1, '2025-10-26 03:31:44.725647+01', '{"updated_at": "2025-10-25T23:49:20.000000Z"}', '{"updated_at": "2025-10-26 02:31:45"}');
INSERT INTO pool_schema.audit_log VALUES (340, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-26 03:31:44.725647+01', '{"status": "paused", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:55:27+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": null, "deactivated_by": null, "subscription_id": 36, "visits_per_week": 1}', '{"status": "paused", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:55:27+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 36, "visits_per_week": 1}');
INSERT INTO pool_schema.audit_log VALUES (341, 'pool_schema.subscriptions', '36', 'UPDATE', 1, '2025-10-26 03:31:44.725647+01', '{"updated_at": "2025-10-26T01:29:47.000000Z", "updated_by": null}', '{"updated_at": "2025-10-26 02:31:45", "updated_by": 1}');
INSERT INTO pool_schema.audit_log VALUES (342, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-26 03:34:05.232163+01', '{"status": "paused", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:55:27+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 36, "visits_per_week": 1}', '{"status": "paused", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:55:27+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": null, "deactivated_by": null, "subscription_id": 36, "visits_per_week": 1}');
INSERT INTO pool_schema.audit_log VALUES (343, 'pool_schema.subscription_allowed_days', '36', 'DELETE', 1, '2025-10-26 03:34:05.252117+01', '{"weekday_id": 1, "subscription_id": 36}', NULL);
INSERT INTO pool_schema.audit_log VALUES (344, 'pool_schema.subscription_allowed_days', '36', 'INSERT', 1, '2025-10-26 03:34:05.257686+01', NULL, '{"weekday_id": 2, "subscription_id": 36}');
INSERT INTO pool_schema.audit_log VALUES (414, 'pool_schema.subscription_allowed_days', '5', 'DELETE', 1, '2025-10-27 22:41:31.950973+01', '{"weekday_id": 2, "subscription_id": 5}', NULL);
INSERT INTO pool_schema.audit_log VALUES (345, 'pool_schema.members', '27', 'UPDATE', 1, '2025-10-26 03:40:35.360487+01', '{"email": "abdoqqqq@gmail.com", "address": "16000", "last_name": "qq", "member_id": 27, "created_at": "2025-10-26T02:03:23.311761+01:00", "created_by": 1, "first_name": "qq", "updated_by": 1, "phone_number": "+213065847555", "date_of_birth": "2025-10-26"}', '{"email": "abdoqqqq@gmail.com", "address": "16000", "last_name": "qq", "member_id": 27, "created_at": "2025-10-26T02:03:23.311761+01:00", "created_by": 1, "first_name": "qq", "updated_by": null, "phone_number": "+213065847555", "date_of_birth": "2025-10-26"}');
INSERT INTO pool_schema.audit_log VALUES (346, 'pool_schema.access_badges', '27', 'UPDATE', 1, '2025-10-26 03:40:35.360487+01', '{"status": "inactive", "badge_id": 34, "badge_uid": "UID-4003", "issued_at": "2025-10-24T23:39:34.854398+01:00", "member_id": null, "expires_at": null}', '{"status": "active", "badge_id": 34, "badge_uid": "UID-4003", "issued_at": "2025-10-26T02:40:35+01:00", "member_id": 27, "expires_at": null}');
INSERT INTO pool_schema.audit_log VALUES (347, 'pool_schema.access_badges', '34', 'UPDATE', 1, '2025-10-26 03:40:35.360487+01', '{"status": "inactive", "issued_at": "2025-10-24 23:39:34.854398+01", "member_id": null}', '{"status": "active", "issued_at": "2025-10-26T02:40:35.375508Z", "member_id": 27}');
INSERT INTO pool_schema.audit_log VALUES (348, 'pool_schema.payments', '36', 'INSERT', 1, '2025-10-26 03:44:25.6912+01', NULL, '{"notes": null, "amount": 1500.00, "payment_id": 21, "payment_date": "2025-10-26T02:44:25+01:00", "payment_method": "cash", "subscription_id": 36, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (349, 'pool_schema.subscription_allowed_days', '36', 'DELETE', 1, '2025-10-26 03:44:29.509789+01', '{"weekday_id": 2, "subscription_id": 36}', NULL);
INSERT INTO pool_schema.audit_log VALUES (350, 'pool_schema.subscription_allowed_days', '36', 'INSERT', 1, '2025-10-26 03:44:29.514006+01', NULL, '{"weekday_id": 2, "subscription_id": 36}');
INSERT INTO pool_schema.audit_log VALUES (351, 'pool_schema.access_badges', '39', 'INSERT', 1, '2025-10-26 03:47:25.174024+01', NULL, '{"status": "inactive", "badge_id": 39, "badge_uid": "UID-50001", "issued_at": "2025-10-26T02:47:25+01:00", "member_id": null, "expires_at": "2026-01-26T00:00:00+01:00"}');
INSERT INTO pool_schema.audit_log VALUES (352, 'pool_schema.access_badges', '39', 'CREATE', 1, '2025-10-26 03:47:25.184145+01', NULL, '{"status": "inactive", "badge_id": 39, "badge_uid": "UID-50001", "issued_at": "2025-10-26T02:47:25.173149Z", "member_id": null, "expires_at": "2026-01-26"}');
INSERT INTO pool_schema.audit_log VALUES (353, 'pool_schema.payments', '2', 'INSERT', 5, '2025-10-26 16:49:29.917861+01', NULL, '{"notes": null, "amount": 200.00, "payment_id": 22, "payment_date": "2025-10-26T15:49:29+01:00", "payment_method": "cash", "subscription_id": 2, "received_by_staff_id": 5}');
INSERT INTO pool_schema.audit_log VALUES (354, 'pool_schema.payments', '2', 'INSERT', 5, '2025-10-26 16:49:42.435687+01', NULL, '{"notes": null, "amount": 600.00, "payment_id": 23, "payment_date": "2025-10-26T15:49:42+01:00", "payment_method": "transfer", "subscription_id": 2, "received_by_staff_id": 5}');
INSERT INTO pool_schema.audit_log VALUES (355, 'pool_schema.subscriptions', '2', 'UPDATE', 5, '2025-10-26 16:49:45.908269+01', '{"status": "paused", "plan_id": 1, "end_date": "2025-10-23", "member_id": 2, "paused_at": null, "created_at": "2025-10-22T14:22:55.308291+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-20", "updated_by": 1, "deactivated_by": null, "subscription_id": 2, "visits_per_week": null}', '{"status": "paused", "plan_id": 1, "end_date": "2025-10-23", "member_id": 2, "paused_at": null, "created_at": "2025-10-22T14:22:55.308291+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-20", "updated_by": null, "deactivated_by": null, "subscription_id": 2, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (356, 'pool_schema.staff', '6', 'UPDATE', NULL, '2025-10-26 16:54:48.238529+01', '{"role_id": 4, "staff_id": 6, "username": "maintenance", "is_active": true, "last_name": "Fix", "created_at": "2025-10-21T17:57:39+01:00", "first_name": "Mounir", "password_hash": "$2y$12$zALPn94XAmg0RDgirIG4XOX.8lnROwSd.XxcvNoKt9ixaHzT2i97G"}', '{"role_id": 5, "staff_id": 6, "username": "maintenance", "is_active": true, "last_name": "Fix", "created_at": "2025-10-21T17:57:39+01:00", "first_name": "Mounir", "password_hash": "$2y$12$zALPn94XAmg0RDgirIG4XOX.8lnROwSd.XxcvNoKt9ixaHzT2i97G"}');
INSERT INTO pool_schema.audit_log VALUES (357, 'pool_schema.staff', '5', 'UPDATE', NULL, '2025-10-26 16:55:48.932513+01', '{"role_id": 3, "staff_id": 5, "username": "financer", "is_active": true, "last_name": "Fin", "created_at": "2025-10-21T17:57:38+01:00", "first_name": "Sara", "password_hash": "$2y$12$4HJ78y7LccKy/42HHwU5buAdwS/wiJbEvLG26LFXOPGlUBn3kachq"}', '{"role_id": 5, "staff_id": 5, "username": "financer", "is_active": true, "last_name": "Fin", "created_at": "2025-10-21T17:57:38+01:00", "first_name": "Sara", "password_hash": "$2y$12$4HJ78y7LccKy/42HHwU5buAdwS/wiJbEvLG26LFXOPGlUBn3kachq"}');
INSERT INTO pool_schema.audit_log VALUES (358, 'pool_schema.roles', NULL, 'UPDATE', NULL, '2025-10-26 16:57:52.995976+01', '{"role_id": 5, "role_name": "réceptionniste"}', '{"role_id": 5, "role_name": "receptionniste"}');
INSERT INTO pool_schema.audit_log VALUES (359, 'pool_schema.members', '12', 'UPDATE', 1, '2025-10-26 17:44:20.58169+01', '{"email": "user10@gmail.com", "address": null, "last_name": "u10", "member_id": 12, "created_at": "2025-10-23T00:31:51.715986+01:00", "created_by": 1, "first_name": "user10", "updated_by": 1, "phone_number": "+21310003258", "date_of_birth": null}', '{"email": "user10@gmail.com", "address": null, "last_name": "karim", "member_id": 12, "created_at": "2025-10-23T00:31:51.715986+01:00", "created_by": 1, "first_name": "amine", "updated_by": null, "phone_number": "+21310003258", "date_of_birth": "2004-10-13"}');
INSERT INTO pool_schema.audit_log VALUES (360, 'pool_schema.access_badges', '8', 'UPDATE', 1, '2025-10-26 17:44:20.58169+01', '{"status": "active", "badge_id": 8, "badge_uid": "B-68F96967BBEE1", "issued_at": "2025-10-22T23:31:51+01:00", "member_id": 12, "expires_at": null}', '{"status": "inactive", "badge_id": 8, "badge_uid": "B-68F96967BBEE1", "issued_at": "2025-10-22T23:31:51+01:00", "member_id": null, "expires_at": null}');
INSERT INTO pool_schema.audit_log VALUES (361, 'pool_schema.access_badges', '8', 'UPDATE', 1, '2025-10-26 17:44:20.58169+01', '{"status": "active", "member_id": 12}', '{"status": "inactive", "member_id": null}');
INSERT INTO pool_schema.audit_log VALUES (362, 'pool_schema.access_badges', '12', 'UPDATE', 1, '2025-10-26 17:44:20.58169+01', '{"status": "inactive", "badge_id": 16, "badge_uid": "UID-1004", "issued_at": "2025-10-24T23:39:34.854398+01:00", "member_id": null, "expires_at": null}', '{"status": "active", "badge_id": 16, "badge_uid": "UID-1004", "issued_at": "2025-10-26T16:44:20+01:00", "member_id": 12, "expires_at": null}');
INSERT INTO pool_schema.audit_log VALUES (363, 'pool_schema.access_badges', '16', 'UPDATE', 1, '2025-10-26 17:44:20.58169+01', '{"status": "inactive", "issued_at": "2025-10-24 23:39:34.854398+01", "member_id": null}', '{"status": "active", "issued_at": "2025-10-26T16:44:20.827162Z", "member_id": 12}');
INSERT INTO pool_schema.audit_log VALUES (364, 'pool_schema.subscriptions', '17', 'UPDATE', 1, '2025-10-26 17:44:20.58169+01', '{"updated_at": "2025-10-24T22:46:17.487819Z"}', '{"updated_at": "2025-10-26 16:44:20"}');
INSERT INTO pool_schema.audit_log VALUES (365, 'pool_schema.access_badges', '29', 'DELETE', 1, '2025-10-26 17:44:36.676158+01', '{"status": "active", "badge_id": 18, "badge_uid": "UID-2001", "issued_at": "2025-10-26T02:24:02+01:00", "member_id": 29, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (366, 'pool_schema.access_badges', '18', 'DELETE', 1, '2025-10-26 17:44:36.691466+01', '{"status": "active", "badge_id": 18, "badge_uid": "UID-2001", "issued_at": "2025-10-26 02:24:02+01", "member_id": 29, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (367, 'pool_schema.members', '3', 'UPDATE', 1, '2025-10-26 17:57:09.592776+01', '{"email": "abdo@gmail.com", "address": "16000", "last_name": "Kohil", "member_id": 3, "created_at": "2025-10-22T14:09:21.00445+01:00", "created_by": null, "first_name": "Abd elhak", "updated_by": null, "phone_number": "+213065847555", "date_of_birth": "1994-11-02"}', '{"email": "abdo@gmail.com", "address": "16000", "last_name": "Ka", "member_id": 3, "created_at": "2025-10-22T14:09:21.00445+01:00", "created_by": null, "first_name": "Abd elhak", "updated_by": null, "phone_number": "+213065847555", "date_of_birth": "1994-11-02"}');
INSERT INTO pool_schema.audit_log VALUES (368, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-26 17:57:09.592776+01', '{"status": "paused", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:55:27+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": null, "deactivated_by": null, "subscription_id": 36, "visits_per_week": 1}', '{"status": "paused", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:55:27+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 36, "visits_per_week": 1}');
INSERT INTO pool_schema.audit_log VALUES (369, 'pool_schema.subscriptions', '36', 'UPDATE', 1, '2025-10-26 17:57:09.592776+01', '{"updated_at": "2025-10-26T01:44:29.000000Z", "updated_by": null}', '{"updated_at": "2025-10-26 16:57:09", "updated_by": 1}');
INSERT INTO pool_schema.audit_log VALUES (370, 'pool_schema.subscriptions', '22', 'UPDATE', 1, '2025-10-26 17:57:09.592776+01', '{"updated_at": "2025-10-26T01:31:44.000000Z"}', '{"updated_at": "2025-10-26 16:57:09"}');
INSERT INTO pool_schema.audit_log VALUES (371, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-26 17:57:09.592776+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:57:31.310217+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "deactivated_by": null, "subscription_id": 27, "visits_per_week": null}', '{"status": "paused", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:57:31.310217+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "deactivated_by": null, "subscription_id": 27, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (372, 'pool_schema.subscriptions', '27', 'UPDATE', 1, '2025-10-26 17:57:09.592776+01', '{"status": "active", "updated_at": "2025-10-26T01:31:44.000000Z"}', '{"status": "paused", "updated_at": "2025-10-26 16:57:09"}');
INSERT INTO pool_schema.audit_log VALUES (373, 'pool_schema.subscriptions', '29', 'UPDATE', 1, '2025-10-26 17:57:09.592776+01', '{"updated_at": "2025-10-26T01:31:45.000000Z"}', '{"updated_at": "2025-10-26 16:57:09"}');
INSERT INTO pool_schema.audit_log VALUES (374, 'pool_schema.subscriptions', '28', 'UPDATE', 1, '2025-10-26 17:57:09.592776+01', '{"updated_at": "2025-10-26T01:31:45.000000Z"}', '{"updated_at": "2025-10-26 16:57:09"}');
INSERT INTO pool_schema.audit_log VALUES (375, 'pool_schema.subscriptions', '34', 'UPDATE', 1, '2025-10-26 17:57:09.592776+01', '{"updated_at": "2025-10-26T01:31:45.000000Z"}', '{"updated_at": "2025-10-26 16:57:09"}');
INSERT INTO pool_schema.audit_log VALUES (376, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-26 17:57:09.592776+01', '{"status": "paused", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T18:31:02.837957+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-24", "updated_by": 1, "deactivated_by": null, "subscription_id": 24, "visits_per_week": null}', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T18:31:02.837957+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-24", "updated_by": 1, "deactivated_by": null, "subscription_id": 24, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (377, 'pool_schema.subscriptions', '24', 'UPDATE', 1, '2025-10-26 17:57:09.592776+01', '{"status": "paused", "updated_at": "2025-10-26T01:31:45.000000Z"}', '{"status": "expired", "updated_at": "2025-10-26 16:57:09"}');
INSERT INTO pool_schema.audit_log VALUES (378, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-26 17:59:07.140507+01', '{"status": "paused", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:55:27+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 36, "visits_per_week": 1}', '{"status": "active", "plan_id": 2, "end_date": "2025-11-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:55:27+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": null, "deactivated_by": null, "subscription_id": 36, "visits_per_week": 2}');
INSERT INTO pool_schema.audit_log VALUES (379, 'pool_schema.subscription_allowed_days', '36', 'DELETE', 1, '2025-10-26 17:59:07.159981+01', '{"weekday_id": 2, "subscription_id": 36}', NULL);
INSERT INTO pool_schema.audit_log VALUES (380, 'pool_schema.subscription_allowed_days', '36', 'INSERT', 1, '2025-10-26 17:59:07.164411+01', NULL, '{"weekday_id": 1, "subscription_id": 36}');
INSERT INTO pool_schema.audit_log VALUES (381, 'pool_schema.subscription_allowed_days', '36', 'INSERT', 1, '2025-10-26 17:59:07.168546+01', NULL, '{"weekday_id": 2, "subscription_id": 36}');
INSERT INTO pool_schema.audit_log VALUES (382, 'pool_schema.subscriptions', '22', 'DELETE', 1, '2025-10-26 17:59:19.039045+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-25", "member_id": 22, "paused_at": null, "created_at": "2025-10-25T23:51:49.784514+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_at": "2025-10-25T22:51:49+01:00", "updated_by": 1, "deactivated_by": null, "subscription_id": 31, "visits_per_week": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (383, 'pool_schema.subscriptions', '12', 'INSERT', 1, '2025-10-26 17:59:58.378668+01', NULL, '{"status": "active", "plan_id": 1, "end_date": "2025-10-26", "member_id": 12, "paused_at": null, "created_at": "2025-10-26T16:59:58+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_at": "2025-10-26T16:59:58+01:00", "updated_by": null, "deactivated_by": null, "subscription_id": 40, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (384, 'pool_schema.payments', '40', 'INSERT', 1, '2025-10-26 17:59:58.378668+01', NULL, '{"notes": "especes", "amount": 800.00, "payment_id": 24, "payment_date": "2025-10-26T16:59:58+01:00", "payment_method": "cash", "subscription_id": 40, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (385, 'pool_schema.subscriptions', '3', 'INSERT', 1, '2025-10-26 18:01:04.809173+01', NULL, '{"status": "active", "plan_id": 3, "end_date": "2025-11-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T17:01:04+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_at": "2025-10-26T17:01:04+01:00", "updated_by": null, "deactivated_by": null, "subscription_id": 41, "visits_per_week": 1}');
INSERT INTO pool_schema.audit_log VALUES (386, 'pool_schema.subscription_allowed_days', '41', 'INSERT', 1, '2025-10-26 18:01:04.809173+01', NULL, '{"weekday_id": 1, "subscription_id": 41}');
INSERT INTO pool_schema.audit_log VALUES (387, 'pool_schema.payments', '41', 'INSERT', 1, '2025-10-26 18:01:04.809173+01', NULL, '{"notes": null, "amount": 1000.00, "payment_id": 25, "payment_date": "2025-10-26T17:01:04+01:00", "payment_method": "cash", "subscription_id": 41, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (415, 'pool_schema.subscription_allowed_days', '5', 'DELETE', 1, '2025-10-27 22:41:31.950973+01', '{"weekday_id": 5, "subscription_id": 5}', NULL);
INSERT INTO pool_schema.audit_log VALUES (388, 'pool_schema.payments', '41', 'INSERT', 1, '2025-10-26 18:01:34.574508+01', NULL, '{"notes": "fff", "amount": 2000.00, "payment_id": 26, "payment_date": "2025-10-26T17:01:34+01:00", "payment_method": "cash", "subscription_id": 41, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (389, 'pool_schema.payments', '41', 'INSERT', 1, '2025-10-26 18:01:49.196698+01', NULL, '{"notes": null, "amount": 500.00, "payment_id": 27, "payment_date": "2025-10-26T17:01:49+01:00", "payment_method": "cash", "subscription_id": 41, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (390, 'pool_schema.subscription_allowed_days', '41', 'DELETE', 1, '2025-10-26 18:01:54.027572+01', '{"weekday_id": 1, "subscription_id": 41}', NULL);
INSERT INTO pool_schema.audit_log VALUES (391, 'pool_schema.subscription_allowed_days', '41', 'INSERT', 1, '2025-10-26 18:01:54.035709+01', NULL, '{"weekday_id": 1, "subscription_id": 41}');
INSERT INTO pool_schema.audit_log VALUES (392, 'pool_schema.access_badges', '40', 'INSERT', 1, '2025-10-26 18:03:10.766432+01', NULL, '{"status": "active", "badge_id": 40, "badge_uid": "UID-6001", "issued_at": "2025-10-26T17:03:10+01:00", "member_id": null, "expires_at": "2028-01-30T00:00:00+01:00"}');
INSERT INTO pool_schema.audit_log VALUES (393, 'pool_schema.access_badges', '40', 'CREATE', 1, '2025-10-26 18:03:10.783277+01', NULL, '{"status": "active", "badge_id": 40, "badge_uid": "UID-6001", "issued_at": "2025-10-26T17:03:10.764634Z", "member_id": null, "expires_at": "2028-01-30"}');
INSERT INTO pool_schema.audit_log VALUES (394, 'pool_schema.access_badges', '40', 'UPDATE', 1, '2025-10-26 18:03:20.747209+01', '{"status": "active", "badge_id": 40, "badge_uid": "UID-6001", "issued_at": "2025-10-26T17:03:10+01:00", "member_id": null, "expires_at": "2028-01-30T00:00:00+01:00"}', '{"status": "inactive", "badge_id": 40, "badge_uid": "UID-6001", "issued_at": "2025-10-26T17:03:10+01:00", "member_id": null, "expires_at": null}');
INSERT INTO pool_schema.audit_log VALUES (395, 'pool_schema.access_badges', '40', 'UPDATE', 1, '2025-10-26 18:03:20.761297+01', '{"status": "active", "expires_at": "2028-01-30 00:00:00+01"}', '{"status": "inactive", "expires_at": null}');
INSERT INTO pool_schema.audit_log VALUES (396, 'pool_schema.access_badges', '40', 'DELETE', 1, '2025-10-26 18:03:32.815588+01', '{"status": "inactive", "badge_id": 40, "badge_uid": "UID-6001", "issued_at": "2025-10-26T17:03:10+01:00", "member_id": null, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (397, 'pool_schema.access_badges', '40', 'DELETE', 1, '2025-10-26 18:03:32.83524+01', '{"status": "inactive", "badge_id": 40, "badge_uid": "UID-6001", "issued_at": "2025-10-26 17:03:10+01", "member_id": null, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (398, 'pool_schema.payments', '29', 'INSERT', 1, '2025-10-26 18:05:46.448608+01', NULL, '{"notes": null, "amount": 100.00, "payment_id": 28, "payment_date": "2025-10-26T17:05:46+01:00", "payment_method": "cash", "subscription_id": 29, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (399, 'pool_schema.payments', '28', 'CREATE', 1, '2025-10-26 18:05:46.465205+01', NULL, '{"notes": null, "amount": "100", "payment_id": 28, "payment_date": "2025-10-26 17:05:46", "payment_method": "cash", "subscription_id": "29", "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (400, 'pool_schema.access_badges', '12', 'DELETE', 1, '2025-10-27 22:30:24.944429+01', '{"status": "active", "badge_id": 16, "badge_uid": "UID-1004", "issued_at": "2025-10-26T16:44:20+01:00", "member_id": 12, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (401, 'pool_schema.access_badges', '16', 'DELETE', 1, '2025-10-27 22:30:24.980556+01', '{"status": "active", "badge_id": 16, "badge_uid": "UID-1004", "issued_at": "2025-10-26 16:44:20+01", "member_id": 12, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (402, 'pool_schema.subscriptions', '12', 'DELETE', 1, '2025-10-27 22:30:24.993329+01', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-23", "member_id": 12, "paused_at": null, "created_at": "2025-10-23T00:31:51.715986+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-23", "updated_at": "2025-10-26T16:44:20+01:00", "updated_by": 1, "deactivated_by": null, "subscription_id": 17, "visits_per_week": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (403, 'pool_schema.subscriptions', '17', 'DELETE', 1, '2025-10-27 22:30:25.006892+01', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-23", "member_id": 12, "paused_at": null, "created_at": "2025-10-23 00:31:51.715986+01", "created_by": 1, "resumes_at": null, "start_date": "2025-10-23", "updated_at": "2025-10-26 16:44:20+01", "updated_by": 1, "deactivated_by": null, "subscription_id": 17, "visits_per_week": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (404, 'pool_schema.access_badges', '22', 'DELETE', 1, '2025-10-27 22:30:37.268243+01', '{"status": "active", "badge_id": 11, "badge_uid": "B-68FA72FC5164C", "issued_at": "2025-10-25T21:55:47+01:00", "member_id": 22, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (405, 'pool_schema.access_badges', '11', 'DELETE', 1, '2025-10-27 22:30:37.294128+01', '{"status": "active", "badge_id": 11, "badge_uid": "B-68FA72FC5164C", "issued_at": "2025-10-25 21:55:47+01", "member_id": 22, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (406, 'pool_schema.members', '22', 'DELETE', 1, '2025-10-27 22:30:37.304676+01', '{"email": "user60@gmail.com", "address": "bouira lakhdaria", "last_name": "user60", "member_id": 22, "created_at": "2025-10-25T23:51:49.784514+01:00", "created_by": 1, "first_name": "u60", "updated_at": "2025-10-25T23:51:49.784514+01:00", "updated_by": 1, "phone_number": "+213065847555", "date_of_birth": "2025-10-25"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (407, 'pool_schema.access_logs', '253', 'DELETE', 1, '2025-10-27 22:40:06.797977+01', '{"log_id": 253, "badge_uid": "UNKNOWN", "member_id": 29, "access_time": "2025-10-26 17:03:52+01", "denial_reason": "Aucun badge assigné", "access_decision": "denied"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (408, 'pool_schema.access_logs', '242', 'DELETE', 1, '2025-10-27 22:40:06.805852+01', '{"log_id": 242, "badge_uid": "UID-2001", "member_id": 29, "access_time": "2025-10-26 15:58:23+01", "denial_reason": "Abonnement expiré ou pas encore actif", "access_decision": "denied"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (409, 'pool_schema.access_logs', '239', 'DELETE', 1, '2025-10-27 22:40:06.80944+01', '{"log_id": 239, "badge_uid": "UID-2001", "member_id": 29, "access_time": "2025-10-26 02:44:51+01", "denial_reason": "Abonnement expiré ou pas encore actif", "access_decision": "denied"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (410, 'pool_schema.members', '29', 'DELETE', 1, '2025-10-27 22:40:06.811022+01', '{"email": "abdo.coins10fff@gmail.com", "address": "bouira lakhdaria", "last_name": "ff", "member_id": 29, "created_at": "2025-10-26T02:08:03.761257+01:00", "created_by": 1, "first_name": "fffffff", "updated_at": "2025-10-26T02:08:03.761257+01:00", "updated_by": null, "phone_number": "03215478", "date_of_birth": "2025-10-18"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (411, 'pool_schema.access_badges', '4', 'DELETE', 1, '2025-10-27 22:41:31.914524+01', '{"status": "active", "badge_id": 14, "badge_uid": "UID-1002", "issued_at": "2025-10-25T21:56:09+01:00", "member_id": 4, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (412, 'pool_schema.access_badges', '14', 'DELETE', 1, '2025-10-27 22:41:31.941602+01', '{"status": "active", "badge_id": 14, "badge_uid": "UID-1002", "issued_at": "2025-10-25 21:56:09+01", "member_id": 4, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (413, 'pool_schema.subscriptions', '4', 'DELETE', 1, '2025-10-27 22:41:31.950973+01', '{"status": "paused", "plan_id": 2, "end_date": "2025-10-22", "member_id": 4, "paused_at": null, "created_at": "2025-10-22T17:31:33.250353+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-22", "updated_at": "2025-10-25T21:56:09+01:00", "updated_by": 1, "deactivated_by": null, "subscription_id": 5, "visits_per_week": 2}', NULL);
INSERT INTO pool_schema.audit_log VALUES (416, 'pool_schema.subscriptions', '5', 'DELETE', 1, '2025-10-27 22:41:31.961535+01', '{"status": "paused", "plan_id": 2, "end_date": "2025-10-22", "member_id": 4, "paused_at": null, "created_at": "2025-10-22 17:31:33.250353+01", "created_by": null, "resumes_at": null, "start_date": "2025-10-22", "updated_at": "2025-10-25 21:56:09+01", "updated_by": 1, "deactivated_by": null, "subscription_id": 5, "visits_per_week": 2}', NULL);
INSERT INTO pool_schema.audit_log VALUES (417, 'pool_schema.access_logs', '258', 'DELETE', 1, '2025-10-27 22:41:31.974524+01', '{"log_id": 258, "badge_uid": "UID-1002", "member_id": 4, "access_time": "2025-10-27 21:41:12+01", "denial_reason": "Abonnement expiré ou pas encore actif", "access_decision": "denied"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (418, 'pool_schema.access_logs', '257', 'DELETE', 1, '2025-10-27 22:41:31.977632+01', '{"log_id": 257, "badge_uid": "UID-1002", "member_id": 4, "access_time": "2025-10-27 21:41:10+01", "denial_reason": "Abonnement expiré ou pas encore actif", "access_decision": "denied"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (419, 'pool_schema.access_logs', '256', 'DELETE', 1, '2025-10-27 22:41:31.980692+01', '{"log_id": 256, "badge_uid": "UID-1002", "member_id": 4, "access_time": "2025-10-26 17:04:47+01", "denial_reason": "Abonnement expiré ou pas encore actif", "access_decision": "denied"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (420, 'pool_schema.access_logs', '247', 'DELETE', 1, '2025-10-27 22:41:31.985485+01', '{"log_id": 247, "badge_uid": "UID-1002", "member_id": 4, "access_time": "2025-10-26 16:10:04+01", "denial_reason": "Abonnement expiré ou pas encore actif", "access_decision": "denied"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (421, 'pool_schema.access_logs', '227', 'DELETE', 1, '2025-10-27 22:41:31.990481+01', '{"log_id": 227, "badge_uid": "UNKNOWN", "member_id": 4, "access_time": "2025-10-24 19:54:51+01", "denial_reason": "Aucun badge assigné", "access_decision": "denied"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (422, 'pool_schema.access_logs', '216', 'DELETE', 1, '2025-10-27 22:41:31.993655+01', '{"log_id": 216, "badge_uid": "UNKNOWN", "member_id": 4, "access_time": "2025-10-24 18:29:57+01", "denial_reason": "Aucun badge assigné", "access_decision": "denied"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (423, 'pool_schema.access_logs', '205', 'DELETE', 1, '2025-10-27 22:41:31.996661+01', '{"log_id": 205, "badge_uid": "UNKNOWN", "member_id": 4, "access_time": "2025-10-24 17:34:09+01", "denial_reason": "Aucun badge assigné", "access_decision": "denied"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (424, 'pool_schema.access_logs', '189', 'DELETE', 1, '2025-10-27 22:41:32.000582+01', '{"log_id": 189, "badge_uid": "UNKNOWN", "member_id": 4, "access_time": "2025-10-24 15:06:08+01", "denial_reason": "Aucun badge assigné", "access_decision": "denied"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (425, 'pool_schema.access_logs', '184', 'DELETE', 1, '2025-10-27 22:41:32.003595+01', '{"log_id": 184, "badge_uid": "UNKNOWN", "member_id": 4, "access_time": "2025-10-24 13:20:02+01", "denial_reason": "Aucun badge assigné", "access_decision": "denied"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (426, 'pool_schema.access_logs', '117', 'DELETE', 1, '2025-10-27 22:41:32.006604+01', '{"log_id": 117, "badge_uid": "UNKNOWN", "member_id": 4, "access_time": "2025-10-23 12:35:19+01", "denial_reason": "No badge assigned", "access_decision": "denied"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (427, 'pool_schema.access_logs', '116', 'DELETE', 1, '2025-10-27 22:41:32.009498+01', '{"log_id": 116, "badge_uid": "UNKNOWN", "member_id": 4, "access_time": "2025-10-23 12:35:15+01", "denial_reason": "No badge assigned", "access_decision": "denied"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (428, 'pool_schema.access_logs', '31', 'DELETE', 1, '2025-10-27 22:41:32.01246+01', '{"log_id": 31, "badge_uid": "UNKNOWN", "member_id": 4, "access_time": "2025-10-22 20:32:52+01", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (429, 'pool_schema.access_logs', '29', 'DELETE', 1, '2025-10-27 22:41:32.015941+01', '{"log_id": 29, "badge_uid": "UNKNOWN", "member_id": 4, "access_time": "2025-10-22 20:31:35+01", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (430, 'pool_schema.access_logs', '26', 'DELETE', 1, '2025-10-27 22:41:32.01898+01', '{"log_id": 26, "badge_uid": "UNKNOWN", "member_id": 4, "access_time": "2025-10-22 20:15:19+01", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (431, 'pool_schema.access_logs', '6', 'DELETE', 1, '2025-10-27 22:41:32.021901+01', '{"log_id": 6, "badge_uid": "UNKNOWN", "member_id": 4, "access_time": "2025-10-22 19:23:14+01", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (432, 'pool_schema.access_logs', '5', 'DELETE', 1, '2025-10-27 22:41:32.024771+01', '{"log_id": 5, "badge_uid": "UNKNOWN", "member_id": 4, "access_time": "2025-10-22 19:23:10+01", "denial_reason": "Inactive or missing badge", "access_decision": "denied"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (433, 'pool_schema.members', '4', 'DELETE', 1, '2025-10-27 22:41:32.026088+01', '{"email": "user1@gmail.com", "address": "user address", "last_name": "user1", "member_id": 4, "created_at": "2025-10-22T17:02:02.902549+01:00", "created_by": null, "first_name": "user1", "updated_at": "2025-10-22T17:02:02.902549+01:00", "updated_by": null, "phone_number": "65847555", "date_of_birth": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (434, 'pool_schema.members', '13', 'UPDATE', 1, '2025-10-27 23:25:57.644816+01', '{"email": "user11@gmail.com", "address": null, "last_name": "u11", "member_id": 13, "created_at": "2025-10-23T00:39:54.984917+01:00", "created_by": 1, "first_name": "user11", "updated_by": 1, "phone_number": "02154785", "date_of_birth": null}', '{"email": "user11@gmail.com", "address": null, "last_name": "u11", "member_id": 13, "created_at": "2025-10-23T00:39:54.984917+01:00", "created_by": 1, "first_name": "user11", "updated_by": null, "phone_number": "02154785", "date_of_birth": "2025-10-27"}');
INSERT INTO pool_schema.audit_log VALUES (435, 'pool_schema.subscriptions', '3', 'UPDATE', NULL, '2025-10-27 23:41:09.935323+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:59:14.435357+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 28, "visits_per_week": null}', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:59:14.435357+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 28, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (436, 'pool_schema.subscriptions', '3', 'UPDATE', NULL, '2025-10-27 23:41:09.935323+01', '{"status": "active", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:27:38+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 34, "visits_per_week": 1}', '{"status": "expired", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:27:38+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 34, "visits_per_week": 1}');
INSERT INTO pool_schema.audit_log VALUES (486, 'pool_schema.payments', '25', 'INSERT', 1, '2025-10-28 01:20:09.061152+01', NULL, '{"notes": null, "amount": 30.00, "payment_id": 36, "payment_date": "2025-10-28T00:20:09+01:00", "payment_method": "cash", "subscription_id": 25, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (437, 'pool_schema.subscriptions', '12', 'UPDATE', NULL, '2025-10-27 23:41:09.935323+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-26", "member_id": 12, "paused_at": null, "created_at": "2025-10-26T16:59:58+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": null, "deactivated_by": null, "subscription_id": 40, "visits_per_week": null}', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-26", "member_id": 12, "paused_at": null, "created_at": "2025-10-26T16:59:58+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": null, "deactivated_by": null, "subscription_id": 40, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (438, 'pool_schema.subscriptions', '17', 'UPDATE', NULL, '2025-10-27 23:41:09.935323+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-25", "member_id": 17, "paused_at": null, "created_at": "2025-10-25T00:13:58.437296+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-24", "updated_by": 1, "deactivated_by": null, "subscription_id": 25, "visits_per_week": null}', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-25", "member_id": 17, "paused_at": null, "created_at": "2025-10-25T00:13:58.437296+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-24", "updated_by": 1, "deactivated_by": null, "subscription_id": 25, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (439, 'pool_schema.subscriptions', '18', 'UPDATE', NULL, '2025-10-27 23:41:09.935323+01', '{"status": "active", "plan_id": 3, "end_date": "2025-10-26", "member_id": 18, "paused_at": null, "created_at": "2025-10-25T11:28:55.097094+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "deactivated_by": null, "subscription_id": 26, "visits_per_week": 1}', '{"status": "expired", "plan_id": 3, "end_date": "2025-10-26", "member_id": 18, "paused_at": null, "created_at": "2025-10-25T11:28:55.097094+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "deactivated_by": null, "subscription_id": 26, "visits_per_week": 1}');
INSERT INTO pool_schema.audit_log VALUES (440, 'pool_schema.subscriptions', '21', 'UPDATE', NULL, '2025-10-27 23:41:09.935323+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-25", "member_id": 21, "paused_at": null, "created_at": "2025-10-25T23:41:19.055895+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "deactivated_by": null, "subscription_id": 30, "visits_per_week": null}', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-25", "member_id": 21, "paused_at": null, "created_at": "2025-10-25T23:41:19.055895+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "deactivated_by": null, "subscription_id": 30, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (441, 'pool_schema.subscriptions', '23', 'UPDATE', NULL, '2025-10-27 23:41:09.935323+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-25", "member_id": 23, "paused_at": null, "created_at": "2025-10-25T23:54:38.706317+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "deactivated_by": null, "subscription_id": 32, "visits_per_week": null}', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-25", "member_id": 23, "paused_at": null, "created_at": "2025-10-25T23:54:38.706317+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "deactivated_by": null, "subscription_id": 32, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (442, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-27 23:45:31.627938+01', '{"status": "expired", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T17:37:39.454325+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-22", "updated_by": 1, "deactivated_by": null, "subscription_id": 22, "visits_per_week": 1}', '{"status": "active", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T17:37:39.454325+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-22", "updated_by": 1, "deactivated_by": null, "subscription_id": 22, "visits_per_week": 1}');
INSERT INTO pool_schema.audit_log VALUES (443, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-27 23:45:31.627938+01', '{"status": "paused", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:57:31.310217+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "deactivated_by": null, "subscription_id": 27, "visits_per_week": null}', '{"status": "active", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:57:31.310217+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "deactivated_by": null, "subscription_id": 27, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (444, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-27 23:45:31.627938+01', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T18:31:02.837957+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-24", "updated_by": 1, "deactivated_by": null, "subscription_id": 24, "visits_per_week": null}', '{"status": "active", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T18:31:02.837957+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-24", "updated_by": 1, "deactivated_by": null, "subscription_id": 24, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (445, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-27 23:45:31.627938+01', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:59:14.435357+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 28, "visits_per_week": null}', '{"status": "active", "plan_id": 1, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:59:14.435357+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 28, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (446, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-27 23:45:31.627938+01', '{"status": "expired", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:27:38+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 34, "visits_per_week": 1}', '{"status": "active", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:27:38+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 34, "visits_per_week": 1}');
INSERT INTO pool_schema.audit_log VALUES (487, 'pool_schema.payments', '36', 'CREATE', 1, '2025-10-28 01:20:09.081871+01', NULL, '{"notes": null, "amount": "30", "payment_id": 36, "payment_date": "2025-10-28 00:20:09", "payment_method": "cash", "subscription_id": "25", "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (447, 'pool_schema.subscriptions', '3', 'UPDATE', NULL, '2025-10-28 00:10:14.469694+01', '{"status": "active", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T17:37:39.454325+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-22", "updated_by": 1, "deactivated_by": null, "subscription_id": 22, "visits_per_week": 1}', '{"status": "expired", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T17:37:39.454325+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-22", "updated_by": 1, "deactivated_by": null, "subscription_id": 22, "visits_per_week": 1}');
INSERT INTO pool_schema.audit_log VALUES (448, 'pool_schema.subscriptions', '3', 'UPDATE', NULL, '2025-10-28 00:10:14.469694+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:57:31.310217+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "deactivated_by": null, "subscription_id": 27, "visits_per_week": null}', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:57:31.310217+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "deactivated_by": null, "subscription_id": 27, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (449, 'pool_schema.subscriptions', '3', 'UPDATE', NULL, '2025-10-28 00:10:14.469694+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T18:31:02.837957+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-24", "updated_by": 1, "deactivated_by": null, "subscription_id": 24, "visits_per_week": null}', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T18:31:02.837957+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-24", "updated_by": 1, "deactivated_by": null, "subscription_id": 24, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (450, 'pool_schema.subscriptions', '3', 'UPDATE', NULL, '2025-10-28 00:10:14.469694+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:59:14.435357+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 28, "visits_per_week": null}', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:59:14.435357+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 28, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (451, 'pool_schema.subscriptions', '3', 'UPDATE', NULL, '2025-10-28 00:10:14.469694+01', '{"status": "active", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:27:38+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 34, "visits_per_week": 1}', '{"status": "expired", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:27:38+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 34, "visits_per_week": 1}');
INSERT INTO pool_schema.audit_log VALUES (452, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-28 00:11:54.781599+01', '{"status": "expired", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T17:37:39.454325+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-22", "updated_by": 1, "deactivated_by": null, "subscription_id": 22, "visits_per_week": 1}', '{"status": "active", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T17:37:39.454325+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-22", "updated_by": 1, "deactivated_by": null, "subscription_id": 22, "visits_per_week": 1}');
INSERT INTO pool_schema.audit_log VALUES (453, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-28 00:11:54.781599+01', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:57:31.310217+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "deactivated_by": null, "subscription_id": 27, "visits_per_week": null}', '{"status": "active", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:57:31.310217+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "deactivated_by": null, "subscription_id": 27, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (454, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-28 00:11:54.781599+01', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T18:31:02.837957+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-24", "updated_by": 1, "deactivated_by": null, "subscription_id": 24, "visits_per_week": null}', '{"status": "active", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T18:31:02.837957+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-24", "updated_by": 1, "deactivated_by": null, "subscription_id": 24, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (455, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-28 00:11:54.781599+01', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:59:14.435357+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 28, "visits_per_week": null}', '{"status": "active", "plan_id": 1, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:59:14.435357+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 28, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (456, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-28 00:11:54.781599+01', '{"status": "expired", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:27:38+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 34, "visits_per_week": 1}', '{"status": "active", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:27:38+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 34, "visits_per_week": 1}');
INSERT INTO pool_schema.audit_log VALUES (488, 'pool_schema.payments', '30', 'INSERT', 1, '2025-10-28 01:21:06.765275+01', NULL, '{"notes": null, "amount": 800.00, "payment_id": 37, "payment_date": "2025-10-28T00:21:06+01:00", "payment_method": "cash", "subscription_id": 30, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (457, 'pool_schema.subscriptions', '3', 'UPDATE', NULL, '2025-10-28 00:13:45.828268+01', '{"status": "active", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T17:37:39.454325+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-22", "updated_by": 1, "deactivated_by": null, "subscription_id": 22, "visits_per_week": 1}', '{"status": "expired", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T17:37:39.454325+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-22", "updated_by": 1, "deactivated_by": null, "subscription_id": 22, "visits_per_week": 1}');
INSERT INTO pool_schema.audit_log VALUES (458, 'pool_schema.subscriptions', '3', 'UPDATE', NULL, '2025-10-28 00:13:45.828268+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:57:31.310217+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "deactivated_by": null, "subscription_id": 27, "visits_per_week": null}', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:57:31.310217+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "deactivated_by": null, "subscription_id": 27, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (459, 'pool_schema.subscriptions', '3', 'UPDATE', NULL, '2025-10-28 00:13:45.828268+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T18:31:02.837957+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-24", "updated_by": 1, "deactivated_by": null, "subscription_id": 24, "visits_per_week": null}', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T18:31:02.837957+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-24", "updated_by": 1, "deactivated_by": null, "subscription_id": 24, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (460, 'pool_schema.subscriptions', '3', 'UPDATE', NULL, '2025-10-28 00:13:45.828268+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:59:14.435357+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 28, "visits_per_week": null}', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:59:14.435357+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 28, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (461, 'pool_schema.subscriptions', '3', 'UPDATE', NULL, '2025-10-28 00:13:45.828268+01', '{"status": "active", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:27:38+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 34, "visits_per_week": 1}', '{"status": "expired", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:27:38+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 34, "visits_per_week": 1}');
INSERT INTO pool_schema.audit_log VALUES (462, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-28 00:17:03.516188+01', '{"status": "expired", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T17:37:39.454325+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-22", "updated_by": 1, "deactivated_by": null, "subscription_id": 22, "visits_per_week": 1}', '{"status": "active", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T17:37:39.454325+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-22", "updated_by": 1, "deactivated_by": null, "subscription_id": 22, "visits_per_week": 1}');
INSERT INTO pool_schema.audit_log VALUES (463, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-28 00:17:03.516188+01', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:57:31.310217+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "deactivated_by": null, "subscription_id": 27, "visits_per_week": null}', '{"status": "active", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:57:31.310217+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "deactivated_by": null, "subscription_id": 27, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (464, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-28 00:17:03.516188+01', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T18:31:02.837957+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-24", "updated_by": 1, "deactivated_by": null, "subscription_id": 24, "visits_per_week": null}', '{"status": "active", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T18:31:02.837957+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-24", "updated_by": 1, "deactivated_by": null, "subscription_id": 24, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (465, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-28 00:17:03.516188+01', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:59:14.435357+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 28, "visits_per_week": null}', '{"status": "active", "plan_id": 1, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:59:14.435357+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 28, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (466, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-10-28 00:17:03.516188+01', '{"status": "expired", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:27:38+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 34, "visits_per_week": 1}', '{"status": "active", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:27:38+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 34, "visits_per_week": 1}');
INSERT INTO pool_schema.audit_log VALUES (489, 'pool_schema.payments', '37', 'CREATE', 1, '2025-10-28 01:21:06.772478+01', NULL, '{"notes": null, "amount": "800", "payment_id": 37, "payment_date": "2025-10-28 00:21:06", "payment_method": "cash", "subscription_id": "30", "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (467, 'pool_schema.subscriptions', '3', 'UPDATE', NULL, '2025-10-28 00:17:19.117804+01', '{"status": "active", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T17:37:39.454325+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-22", "updated_by": 1, "deactivated_by": null, "subscription_id": 22, "visits_per_week": 1}', '{"status": "expired", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T17:37:39.454325+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-22", "updated_by": 1, "deactivated_by": null, "subscription_id": 22, "visits_per_week": 1}');
INSERT INTO pool_schema.audit_log VALUES (468, 'pool_schema.subscriptions', '3', 'UPDATE', NULL, '2025-10-28 00:17:19.117804+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:57:31.310217+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "deactivated_by": null, "subscription_id": 27, "visits_per_week": null}', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:57:31.310217+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "deactivated_by": null, "subscription_id": 27, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (469, 'pool_schema.subscriptions', '3', 'UPDATE', NULL, '2025-10-28 00:17:19.117804+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T18:31:02.837957+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-24", "updated_by": 1, "deactivated_by": null, "subscription_id": 24, "visits_per_week": null}', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-25", "member_id": 3, "paused_at": null, "created_at": "2025-10-24T18:31:02.837957+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-24", "updated_by": 1, "deactivated_by": null, "subscription_id": 24, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (470, 'pool_schema.subscriptions', '3', 'UPDATE', NULL, '2025-10-28 00:17:19.117804+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:59:14.435357+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 28, "visits_per_week": null}', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:59:14.435357+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 28, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (471, 'pool_schema.subscriptions', '3', 'UPDATE', NULL, '2025-10-28 00:17:19.117804+01', '{"status": "active", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:27:38+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 34, "visits_per_week": 1}', '{"status": "expired", "plan_id": 3, "end_date": "2025-10-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-26T00:27:38+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": 1, "deactivated_by": null, "subscription_id": 34, "visits_per_week": 1}');
INSERT INTO pool_schema.audit_log VALUES (472, 'pool_schema.payments', '29', 'INSERT', 1, '2025-10-28 00:26:25.226495+01', NULL, '{"notes": null, "amount": 3500.00, "payment_id": 29, "payment_date": "2025-10-27T23:26:25+01:00", "payment_method": "cash", "subscription_id": 29, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (473, 'pool_schema.payments', '29', 'CREATE', 1, '2025-10-28 00:26:25.279863+01', NULL, '{"notes": null, "amount": "3500", "payment_id": 29, "payment_date": "2025-10-27 23:26:25", "payment_method": "cash", "subscription_id": "29", "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (474, 'pool_schema.payments', '36', 'INSERT', 1, '2025-10-28 00:53:47.787678+01', NULL, '{"notes": null, "amount": 500.00, "payment_id": 30, "payment_date": "2025-10-27T23:53:47+01:00", "payment_method": "cash", "subscription_id": 36, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (475, 'pool_schema.payments', '30', 'CREATE', 1, '2025-10-28 00:53:47.805901+01', NULL, '{"notes": null, "amount": "500", "payment_id": 30, "payment_date": "2025-10-27 23:53:47", "payment_method": "cash", "subscription_id": "36", "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (476, 'pool_schema.payments', '27', 'INSERT', 1, '2025-10-28 00:54:48.811231+01', NULL, '{"notes": null, "amount": 800.00, "payment_id": 31, "payment_date": "2025-10-27T23:54:48+01:00", "payment_method": "cash", "subscription_id": 27, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (477, 'pool_schema.payments', '31', 'CREATE', 1, '2025-10-28 00:54:48.825382+01', NULL, '{"notes": null, "amount": "800", "payment_id": 31, "payment_date": "2025-10-27 23:54:48", "payment_method": "cash", "subscription_id": "27", "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (478, 'pool_schema.payments', '12', 'INSERT', 1, '2025-10-28 00:56:25.673192+01', NULL, '{"notes": null, "amount": 4000.00, "payment_id": 32, "payment_date": "2025-10-27T23:56:25+01:00", "payment_method": "cash", "subscription_id": 12, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (479, 'pool_schema.payments', '32', 'CREATE', 1, '2025-10-28 00:56:25.6899+01', NULL, '{"notes": null, "amount": "4000", "payment_id": 32, "payment_date": "2025-10-27 23:56:25", "payment_method": "cash", "subscription_id": "12", "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (480, 'pool_schema.payments', '34', 'INSERT', 1, '2025-10-28 01:01:27.780769+01', NULL, '{"notes": null, "amount": 330.00, "payment_id": 33, "payment_date": "2025-10-28T00:01:27+01:00", "payment_method": "cash", "subscription_id": 34, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (481, 'pool_schema.payments', '33', 'CREATE', 1, '2025-10-28 01:01:27.810818+01', NULL, '{"notes": null, "amount": "330", "payment_id": 33, "payment_date": "2025-10-28 00:01:27", "payment_method": "cash", "subscription_id": "34", "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (482, 'pool_schema.payments', '18', 'INSERT', 1, '2025-10-28 01:06:05.706635+01', NULL, '{"notes": null, "amount": 800.00, "payment_id": 34, "payment_date": "2025-10-28T00:06:05+01:00", "payment_method": "cash", "subscription_id": 18, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (483, 'pool_schema.payments', '34', 'CREATE', 1, '2025-10-28 01:06:05.735533+01', NULL, '{"notes": null, "amount": "800", "payment_id": 34, "payment_date": "2025-10-28 00:06:05", "payment_method": "cash", "subscription_id": "18", "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (484, 'pool_schema.payments', '25', 'INSERT', 1, '2025-10-28 01:09:24.203613+01', NULL, '{"notes": null, "amount": 770.00, "payment_id": 35, "payment_date": "2025-10-28T00:09:24+01:00", "payment_method": "cash", "subscription_id": 25, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (485, 'pool_schema.payments', '35', 'CREATE', 1, '2025-10-28 01:09:24.220391+01', NULL, '{"notes": null, "amount": "770", "payment_id": 35, "payment_date": "2025-10-28 00:09:24", "payment_method": "cash", "subscription_id": "25", "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (515, 'pool_schema.subscription_allowed_days', '10', 'DELETE', NULL, '2025-10-30 02:59:08.8424+01', '{"weekday_id": 1, "subscription_id": 10}', NULL);
INSERT INTO pool_schema.audit_log VALUES (490, 'pool_schema.payments', '32', 'INSERT', 1, '2025-10-28 01:28:23.750471+01', NULL, '{"notes": null, "amount": 800.00, "payment_id": 38, "payment_date": "2025-10-28T00:28:23+01:00", "payment_method": "cash", "subscription_id": 32, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (491, 'pool_schema.subscriptions', '13', 'UPDATE', 1, '2025-10-28 01:29:19.384076+01', '{"status": "paused", "plan_id": 1, "end_date": "2025-10-23", "member_id": 13, "paused_at": null, "created_at": "2025-10-23T00:39:54.984917+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-23", "updated_by": 1, "deactivated_by": null, "subscription_id": 18, "visits_per_week": null}', '{"status": "active", "plan_id": 1, "end_date": "2025-10-23", "member_id": 13, "paused_at": null, "created_at": "2025-10-23T00:39:54.984917+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-23", "updated_by": 1, "deactivated_by": null, "subscription_id": 18, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (492, 'pool_schema.subscriptions', '12', 'UPDATE', 1, '2025-10-28 01:30:04.712956+01', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-26", "member_id": 12, "paused_at": null, "created_at": "2025-10-26T16:59:58+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": null, "deactivated_by": null, "subscription_id": 40, "visits_per_week": null}', '{"status": "cancelled", "plan_id": 1, "end_date": "2025-10-26", "member_id": 12, "paused_at": null, "created_at": "2025-10-26T16:59:58+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-26", "updated_by": null, "deactivated_by": null, "subscription_id": 40, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (493, 'pool_schema.access_badges', '27', 'DELETE', 1, '2025-10-28 01:30:29.809985+01', '{"status": "active", "badge_id": 34, "badge_uid": "UID-4003", "issued_at": "2025-10-26T02:40:35+01:00", "member_id": 27, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (494, 'pool_schema.members', '27', 'DELETE', 1, '2025-10-28 01:30:29.831802+01', '{"email": "abdoqqqq@gmail.com", "address": "16000", "last_name": "qq", "member_id": 27, "created_at": "2025-10-26T02:03:23.311761+01:00", "created_by": 1, "first_name": "qq", "updated_at": "2025-10-26T02:03:23.311761+01:00", "updated_by": null, "phone_number": "+213065847555", "date_of_birth": "2025-10-26"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (495, 'pool_schema.payments', '9', 'INSERT', 1, '2025-10-28 01:31:59.922449+01', NULL, '{"notes": null, "amount": 3500.00, "payment_id": 39, "payment_date": "2025-10-28T00:31:59+01:00", "payment_method": "cash", "subscription_id": 9, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (496, 'pool_schema.access_badges', '5', 'UPDATE', 1, '2025-10-28 01:32:21.539373+01', '{"status": "inactive", "badge_id": 8, "badge_uid": "B-68F96967BBEE1", "issued_at": "2025-10-22T23:31:51+01:00", "member_id": null, "expires_at": null}', '{"status": "active", "badge_id": 8, "badge_uid": "B-68F96967BBEE1", "issued_at": "2025-10-28T00:32:21+01:00", "member_id": 5, "expires_at": null}');
INSERT INTO pool_schema.audit_log VALUES (497, 'pool_schema.access_badges', '5', 'DELETE', 1, '2025-10-28 01:32:48.023807+01', '{"status": "active", "badge_id": 8, "badge_uid": "B-68F96967BBEE1", "issued_at": "2025-10-28T00:32:21+01:00", "member_id": 5, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (498, 'pool_schema.access_badges', '12', 'UPDATE', 1, '2025-10-28 01:44:45.849407+01', '{"status": "inactive", "badge_id": 19, "badge_uid": "UID-2002", "issued_at": "2025-10-24T23:39:34.854398+01:00", "member_id": null, "expires_at": null}', '{"status": "active", "badge_id": 19, "badge_uid": "UID-2002", "issued_at": "2025-10-28T00:44:45+01:00", "member_id": 12, "expires_at": null}');
INSERT INTO pool_schema.audit_log VALUES (499, 'pool_schema.access_badges', '5', 'UPDATE', 1, '2025-10-28 01:45:17.613547+01', '{"status": "inactive", "badge_id": 20, "badge_uid": "UID-2003", "issued_at": "2025-10-24T23:39:34.854398+01:00", "member_id": null, "expires_at": null}', '{"status": "active", "badge_id": 20, "badge_uid": "UID-2003", "issued_at": "2025-10-28T00:45:17+01:00", "member_id": 5, "expires_at": null}');
INSERT INTO pool_schema.audit_log VALUES (500, 'pool_schema.access_badges', '6', 'UPDATE', 1, '2025-10-28 01:45:30.140602+01', '{"status": "inactive", "badge_id": 39, "badge_uid": "UID-50001", "issued_at": "2025-10-26T02:47:25+01:00", "member_id": null, "expires_at": "2026-01-26T00:00:00+01:00"}', '{"status": "active", "badge_id": 39, "badge_uid": "UID-50001", "issued_at": "2025-10-28T00:45:30+01:00", "member_id": 6, "expires_at": "2026-01-26T00:00:00+01:00"}');
INSERT INTO pool_schema.audit_log VALUES (501, 'pool_schema.access_badges', '7', 'UPDATE', 1, '2025-10-28 01:45:42.364155+01', '{"status": "inactive", "badge_id": 27, "badge_uid": "UID-3005", "issued_at": "2025-10-24T23:39:34.854398+01:00", "member_id": null, "expires_at": null}', '{"status": "active", "badge_id": 27, "badge_uid": "UID-3005", "issued_at": "2025-10-28T00:45:42+01:00", "member_id": 7, "expires_at": null}');
INSERT INTO pool_schema.audit_log VALUES (502, 'pool_schema.subscriptions', '13', 'UPDATE', NULL, '2025-10-30 00:16:08.89299+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-23", "member_id": 13, "paused_at": null, "created_at": "2025-10-23T00:39:54.984917+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-23", "updated_by": 1, "deactivated_by": null, "subscription_id": 18, "visits_per_week": null}', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-23", "member_id": 13, "paused_at": null, "created_at": "2025-10-23T00:39:54.984917+01:00", "created_by": 1, "resumes_at": null, "start_date": "2025-10-23", "updated_by": 1, "deactivated_by": null, "subscription_id": 18, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (503, 'pool_schema.subscription_allowed_days', '23', 'DELETE', NULL, '2025-10-30 02:59:08.8424+01', '{"weekday_id": 1, "subscription_id": 23}', NULL);
INSERT INTO pool_schema.audit_log VALUES (504, 'pool_schema.subscription_allowed_days', '22', 'DELETE', NULL, '2025-10-30 02:59:08.8424+01', '{"weekday_id": 5, "subscription_id": 22}', NULL);
INSERT INTO pool_schema.audit_log VALUES (505, 'pool_schema.subscription_allowed_days', '26', 'DELETE', NULL, '2025-10-30 02:59:08.8424+01', '{"weekday_id": 1, "subscription_id": 26}', NULL);
INSERT INTO pool_schema.audit_log VALUES (506, 'pool_schema.subscription_allowed_days', '29', 'DELETE', NULL, '2025-10-30 02:59:08.8424+01', '{"weekday_id": 1, "subscription_id": 29}', NULL);
INSERT INTO pool_schema.audit_log VALUES (507, 'pool_schema.subscription_allowed_days', '29', 'DELETE', NULL, '2025-10-30 02:59:08.8424+01', '{"weekday_id": 5, "subscription_id": 29}', NULL);
INSERT INTO pool_schema.audit_log VALUES (508, 'pool_schema.subscription_allowed_days', '34', 'DELETE', NULL, '2025-10-30 02:59:08.8424+01', '{"weekday_id": 1, "subscription_id": 34}', NULL);
INSERT INTO pool_schema.audit_log VALUES (509, 'pool_schema.subscription_allowed_days', '36', 'DELETE', NULL, '2025-10-30 02:59:08.8424+01', '{"weekday_id": 1, "subscription_id": 36}', NULL);
INSERT INTO pool_schema.audit_log VALUES (510, 'pool_schema.subscription_allowed_days', '36', 'DELETE', NULL, '2025-10-30 02:59:08.8424+01', '{"weekday_id": 2, "subscription_id": 36}', NULL);
INSERT INTO pool_schema.audit_log VALUES (511, 'pool_schema.subscription_allowed_days', '41', 'DELETE', NULL, '2025-10-30 02:59:08.8424+01', '{"weekday_id": 1, "subscription_id": 41}', NULL);
INSERT INTO pool_schema.audit_log VALUES (512, 'pool_schema.subscription_allowed_days', '13', 'DELETE', NULL, '2025-10-30 02:59:08.8424+01', '{"weekday_id": 1, "subscription_id": 13}', NULL);
INSERT INTO pool_schema.audit_log VALUES (513, 'pool_schema.subscription_allowed_days', '12', 'DELETE', NULL, '2025-10-30 02:59:08.8424+01', '{"weekday_id": 1, "subscription_id": 12}', NULL);
INSERT INTO pool_schema.audit_log VALUES (514, 'pool_schema.subscription_allowed_days', '12', 'DELETE', NULL, '2025-10-30 02:59:08.8424+01', '{"weekday_id": 6, "subscription_id": 12}', NULL);
INSERT INTO pool_schema.audit_log VALUES (517, 'pool_schema.staff', '13', 'INSERT', 1, '2025-10-31 13:52:43.588536+01', NULL, '{"role_id": 5, "staff_id": 13, "username": "user1", "is_active": true, "last_name": "recept", "created_at": "2025-10-31T13:52:43+01:00", "first_name": "recep", "password_hash": "$2y$12$a7m1bYYi/FFh46F7i2eereq5R3vBF65S3/Pkm.JBdY94DVVTGjs4y"}');
INSERT INTO pool_schema.audit_log VALUES (518, 'pool_schema.staff', '13', 'UPDATE', NULL, '2025-10-31 13:54:12.656132+01', '{"role_id": 5, "staff_id": 13, "username": "user1", "is_active": true, "last_name": "recept", "created_at": "2025-10-31T13:52:43+01:00", "first_name": "recep", "password_hash": "$2y$12$a7m1bYYi/FFh46F7i2eereq5R3vBF65S3/Pkm.JBdY94DVVTGjs4y"}', '{"role_id": 5, "staff_id": 13, "username": "user1", "is_active": true, "last_name": "recept", "created_at": "2025-10-31T13:52:43+01:00", "first_name": "recep", "password_hash": "$2y$12$LwXyMXpLUdCCITe.oen02.G75EszsMIkFo8S8abnAGtb3/FF6NgDG"}');
INSERT INTO pool_schema.audit_log VALUES (519, 'pool_schema.subscriptions', '3', 'INSERT', 1, '2025-10-31 16:29:53.201445+01', NULL, '{"status": "active", "plan_id": 2, "end_date": "2025-11-30", "member_id": 3, "paused_at": null, "created_at": "2025-10-31T16:29:53+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-31", "updated_at": "2025-10-31T16:29:53+01:00", "updated_by": null, "activity_id": 1, "deactivated_by": null, "subscription_id": 42, "visits_per_week": 2}');
INSERT INTO pool_schema.audit_log VALUES (520, 'pool_schema.subscription_allowed_days', '42', 'INSERT', 1, '2025-10-31 16:29:53.201445+01', NULL, '{"weekday_id": 1, "subscription_id": 42}');
INSERT INTO pool_schema.audit_log VALUES (521, 'pool_schema.subscription_allowed_days', '42', 'INSERT', 1, '2025-10-31 16:29:53.201445+01', NULL, '{"weekday_id": 2, "subscription_id": 42}');
INSERT INTO pool_schema.audit_log VALUES (522, 'pool_schema.payments', '42', 'INSERT', 1, '2025-10-31 16:29:53.201445+01', NULL, '{"notes": null, "amount": 4000.00, "payment_id": 40, "payment_date": "2025-10-31T16:29:53+01:00", "payment_method": "cash", "subscription_id": 42, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (523, 'pool_schema.subscription_allowed_days', '42', 'DELETE', 1, '2025-10-31 16:39:42.622291+01', '{"weekday_id": 1, "subscription_id": 42}', NULL);
INSERT INTO pool_schema.audit_log VALUES (524, 'pool_schema.subscription_allowed_days', '42', 'DELETE', 1, '2025-10-31 16:39:42.622291+01', '{"weekday_id": 2, "subscription_id": 42}', NULL);
INSERT INTO pool_schema.audit_log VALUES (525, 'pool_schema.subscription_allowed_days', '42', 'INSERT', 1, '2025-10-31 16:39:42.628763+01', NULL, '{"weekday_id": 1, "subscription_id": 42}');
INSERT INTO pool_schema.audit_log VALUES (526, 'pool_schema.subscription_allowed_days', '42', 'INSERT', 1, '2025-10-31 16:39:42.631533+01', NULL, '{"weekday_id": 2, "subscription_id": 42}');
INSERT INTO pool_schema.audit_log VALUES (527, 'pool_schema.payments', '42', 'INSERT', 1, '2025-10-31 16:39:42.633295+01', NULL, '{"notes": null, "amount": 500.00, "payment_id": 41, "payment_date": "2025-10-31T16:39:42+01:00", "payment_method": "cash", "subscription_id": 42, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (528, 'pool_schema.subscriptions', '3', 'INSERT', 1, '2025-10-31 16:58:03.575786+01', NULL, '{"status": "active", "plan_id": 1, "end_date": "2025-10-31", "member_id": 3, "paused_at": null, "created_at": "2025-10-31T16:58:03+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-31", "updated_at": "2025-10-31T16:58:03+01:00", "updated_by": null, "activity_id": 5, "deactivated_by": null, "subscription_id": 43, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (529, 'pool_schema.payments', '43', 'INSERT', 1, '2025-10-31 16:58:03.575786+01', NULL, '{"notes": null, "amount": 1000.00, "payment_id": 42, "payment_date": "2025-10-31T16:58:03+01:00", "payment_method": "cash", "subscription_id": 43, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (530, 'pool_schema.subscriptions', '12', 'INSERT', 1, '2025-10-31 16:59:52.409028+01', NULL, '{"status": "active", "plan_id": 1, "end_date": "2025-10-31", "member_id": 12, "paused_at": null, "created_at": "2025-10-31T16:59:52+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-31", "updated_at": "2025-10-31T16:59:52+01:00", "updated_by": null, "activity_id": 5, "deactivated_by": null, "subscription_id": 44, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (531, 'pool_schema.payments', '44', 'INSERT', 1, '2025-10-31 16:59:52.409028+01', NULL, '{"notes": null, "amount": 500.00, "payment_id": 43, "payment_date": "2025-10-31T16:59:52+01:00", "payment_method": "cash", "subscription_id": 44, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (532, 'pool_schema.payments', '44', 'INSERT', 1, '2025-10-31 17:43:51.577054+01', NULL, '{"notes": null, "amount": 20.00, "payment_id": 44, "payment_date": "2025-10-31T17:43:51+01:00", "payment_method": "cash", "subscription_id": 44, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (533, 'pool_schema.payments', '44', 'INSERT', 1, '2025-10-31 17:44:31.083609+01', NULL, '{"notes": null, "amount": 680.00, "payment_id": 45, "payment_date": "2025-10-31T17:44:31+01:00", "payment_method": "cash", "subscription_id": 44, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (560, 'pool_schema.subscription_allowed_days', '56', 'DELETE', NULL, '2025-11-01 19:38:37.953566+01', '{"weekday_id": 5, "subscription_id": 56}', NULL);
INSERT INTO pool_schema.audit_log VALUES (561, 'pool_schema.subscription_allowed_days', '57', 'DELETE', NULL, '2025-11-01 19:38:37.953566+01', '{"weekday_id": 5, "subscription_id": 57}', NULL);
INSERT INTO pool_schema.audit_log VALUES (545, 'pool_schema.subscriptions', '12', 'INSERT', 1, '2025-10-31 18:10:50.085882+01', NULL, '{"status": "active", "plan_id": 2, "end_date": "2025-11-08", "member_id": 12, "paused_at": null, "created_at": "2025-10-31T18:10:50+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-11-01", "updated_at": "2025-10-31T18:10:50+01:00", "updated_by": null, "activity_id": 1, "deactivated_by": null, "subscription_id": 56, "visits_per_week": 2}');
INSERT INTO pool_schema.audit_log VALUES (546, 'pool_schema.subscription_allowed_days', '56', 'INSERT', 1, '2025-10-31 18:10:50.085882+01', NULL, '{"weekday_id": 3, "subscription_id": 56}');
INSERT INTO pool_schema.audit_log VALUES (547, 'pool_schema.subscription_allowed_days', '56', 'INSERT', 1, '2025-10-31 18:10:50.085882+01', NULL, '{"weekday_id": 5, "subscription_id": 56}');
INSERT INTO pool_schema.audit_log VALUES (548, 'pool_schema.payments', '56', 'INSERT', 1, '2025-10-31 18:10:50.085882+01', NULL, '{"notes": null, "amount": 10.00, "payment_id": 46, "payment_date": "2025-10-31T18:10:50+01:00", "payment_method": "cash", "subscription_id": 56, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (549, 'pool_schema.subscriptions', '12', 'INSERT', 1, '2025-10-31 18:11:29.260273+01', NULL, '{"status": "active", "plan_id": 3, "end_date": "2025-11-07", "member_id": 12, "paused_at": null, "created_at": "2025-10-31T18:11:29+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-30", "updated_at": "2025-10-31T18:11:29+01:00", "updated_by": null, "activity_id": 1, "deactivated_by": null, "subscription_id": 57, "visits_per_week": 1}');
INSERT INTO pool_schema.audit_log VALUES (550, 'pool_schema.subscription_allowed_days', '57', 'INSERT', 1, '2025-10-31 18:11:29.260273+01', NULL, '{"weekday_id": 5, "subscription_id": 57}');
INSERT INTO pool_schema.audit_log VALUES (551, 'pool_schema.payments', '57', 'INSERT', 1, '2025-10-31 18:11:29.260273+01', NULL, '{"notes": null, "amount": 15.00, "payment_id": 47, "payment_date": "2025-10-31T18:11:29+01:00", "payment_method": "cash", "subscription_id": 57, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (552, 'pool_schema.subscriptions', '3', 'UPDATE', NULL, '2025-11-01 01:04:15.820848+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-31", "member_id": 3, "paused_at": null, "created_at": "2025-10-31T16:58:03+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-31", "updated_by": null, "activity_id": 5, "deactivated_by": null, "subscription_id": 43, "visits_per_week": null}', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-31", "member_id": 3, "paused_at": null, "created_at": "2025-10-31T16:58:03+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-31", "updated_by": null, "activity_id": 5, "deactivated_by": null, "subscription_id": 43, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (553, 'pool_schema.subscriptions', '12', 'UPDATE', NULL, '2025-11-01 01:04:15.820848+01', '{"status": "active", "plan_id": 1, "end_date": "2025-10-31", "member_id": 12, "paused_at": null, "created_at": "2025-10-31T16:59:52+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-31", "updated_by": null, "activity_id": 5, "deactivated_by": null, "subscription_id": 44, "visits_per_week": null}', '{"status": "expired", "plan_id": 1, "end_date": "2025-10-31", "member_id": 12, "paused_at": null, "created_at": "2025-10-31T16:59:52+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-31", "updated_by": null, "activity_id": 5, "deactivated_by": null, "subscription_id": 44, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (554, 'pool_schema.access_badges', '14', 'DELETE', 1, '2025-11-01 13:15:24.778593+01', '{"status": "active", "badge_id": 15, "badge_uid": "UID-1003", "issued_at": "2025-10-25T22:01:34+01:00", "member_id": 14, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (555, 'pool_schema.members', '14', 'DELETE', 1, '2025-11-01 13:15:24.812837+01', '{"email": "user12@gmail.com", "address": null, "last_name": "u12", "member_id": 14, "created_at": "2025-10-23T16:12:03.140689+01:00", "created_by": 1, "first_name": "user12", "updated_at": "2025-10-23T16:12:03.140689+01:00", "updated_by": 1, "phone_number": "03215478", "date_of_birth": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (556, 'pool_schema.access_badges', '17', 'DELETE', 1, '2025-11-01 17:02:28.892352+01', '{"status": "active", "badge_id": 23, "badge_uid": "UID-3001", "issued_at": "2025-10-24T23:39:34.854398+01:00", "member_id": 17, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (557, 'pool_schema.subscription_allowed_days', '42', 'DELETE', NULL, '2025-11-01 19:38:37.953566+01', '{"weekday_id": 1, "subscription_id": 42}', NULL);
INSERT INTO pool_schema.audit_log VALUES (558, 'pool_schema.subscription_allowed_days', '42', 'DELETE', NULL, '2025-11-01 19:38:37.953566+01', '{"weekday_id": 2, "subscription_id": 42}', NULL);
INSERT INTO pool_schema.audit_log VALUES (559, 'pool_schema.subscription_allowed_days', '56', 'DELETE', NULL, '2025-11-01 19:38:37.953566+01', '{"weekday_id": 3, "subscription_id": 56}', NULL);
INSERT INTO pool_schema.audit_log VALUES (562, 'pool_schema.subscription_allowed_days', '57', 'INSERT', 1, '2025-11-01 21:59:03.569474+01', NULL, '{"weekday_id": 5, "subscription_id": 57}');
INSERT INTO pool_schema.audit_log VALUES (563, 'pool_schema.subscriptions', '3', 'INSERT', 1, '2025-11-02 00:31:20.700525+01', NULL, '{"status": "active", "plan_id": 3, "end_date": "2025-12-02", "member_id": 3, "paused_at": null, "created_at": "2025-11-02T00:31:20+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-11-02", "updated_at": "2025-11-02T00:31:20+01:00", "updated_by": null, "activity_id": 1, "deactivated_by": null, "subscription_id": 58, "visits_per_week": 1}');
INSERT INTO pool_schema.audit_log VALUES (564, 'pool_schema.subscription_allowed_days', '58', 'INSERT', 1, '2025-11-02 00:31:20.700525+01', NULL, '{"weekday_id": 1, "subscription_id": 58}');
INSERT INTO pool_schema.audit_log VALUES (565, 'pool_schema.payments', '58', 'INSERT', 1, '2025-11-02 00:31:20.700525+01', NULL, '{"notes": null, "amount": 3500.00, "payment_id": 48, "payment_date": "2025-11-02T00:31:20+01:00", "payment_method": "cash", "subscription_id": 58, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (566, 'pool_schema.subscriptions', '12', 'UPDATE', NULL, '2025-11-09 09:04:34.781116+01', '{"status": "active", "plan_id": 2, "end_date": "2025-11-08", "member_id": 12, "paused_at": null, "created_at": "2025-10-31T18:10:50+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-11-01", "updated_by": null, "activity_id": 1, "deactivated_by": null, "subscription_id": 56, "visits_per_week": 2}', '{"status": "expired", "plan_id": 2, "end_date": "2025-11-08", "member_id": 12, "paused_at": null, "created_at": "2025-10-31T18:10:50+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-11-01", "updated_by": null, "activity_id": 1, "deactivated_by": null, "subscription_id": 56, "visits_per_week": 2}');
INSERT INTO pool_schema.audit_log VALUES (567, 'pool_schema.subscriptions', '12', 'UPDATE', NULL, '2025-11-09 09:04:34.781116+01', '{"status": "active", "plan_id": 3, "end_date": "2025-11-07", "member_id": 12, "paused_at": null, "created_at": "2025-10-31T18:11:29+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-30", "updated_by": null, "activity_id": 1, "deactivated_by": null, "subscription_id": 57, "visits_per_week": 1}', '{"status": "expired", "plan_id": 3, "end_date": "2025-11-07", "member_id": 12, "paused_at": null, "created_at": "2025-10-31T18:11:29+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-30", "updated_by": null, "activity_id": 1, "deactivated_by": null, "subscription_id": 57, "visits_per_week": 1}');
INSERT INTO pool_schema.audit_log VALUES (568, 'pool_schema.subscriptions', '3', 'UPDATE', 1, '2025-11-09 09:08:20.43917+01', '{"status": "active", "plan_id": 2, "end_date": "2025-11-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:04:49+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "activity_id": null, "deactivated_by": null, "subscription_id": 29, "visits_per_week": 2}', '{"status": "cancelled", "plan_id": 2, "end_date": "2025-11-26", "member_id": 3, "paused_at": null, "created_at": "2025-10-25T16:04:49+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-10-25", "updated_by": 1, "activity_id": null, "deactivated_by": null, "subscription_id": 29, "visits_per_week": 2}');
INSERT INTO pool_schema.audit_log VALUES (569, 'pool_schema.members', '30', 'INSERT', 1, '2025-11-09 09:09:20.960988+01', NULL, '{"email": "abdo123@gmail.com", "address": "16000", "last_name": "test", "member_id": 30, "created_at": "2025-11-09T09:09:20.960988+01:00", "created_by": null, "first_name": "Abdo", "updated_at": "2025-11-09T09:09:20.960988+01:00", "updated_by": null, "phone_number": "+21310003258", "date_of_birth": "2025-11-09"}');
INSERT INTO pool_schema.audit_log VALUES (570, 'pool_schema.access_badges', '30', 'UPDATE', 1, '2025-11-09 09:09:20.960988+01', '{"status": "inactive", "badge_id": 21, "badge_uid": "UID-2004", "issued_at": "2025-10-24T23:39:34.854398+01:00", "member_id": null, "expires_at": null}', '{"status": "active", "badge_id": 21, "badge_uid": "UID-2004", "issued_at": "2025-10-24T23:39:34.854398+01:00", "member_id": 30, "expires_at": null}');
INSERT INTO pool_schema.audit_log VALUES (571, 'pool_schema.subscriptions', '30', 'INSERT', 1, '2025-11-09 09:09:20.960988+01', NULL, '{"status": "active", "plan_id": 2, "end_date": "2025-11-15", "member_id": 30, "paused_at": null, "created_at": "2025-11-09T09:09:20.960988+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-11-09", "updated_at": "2025-11-09T09:09:20+01:00", "updated_by": null, "activity_id": null, "deactivated_by": null, "subscription_id": 59, "visits_per_week": 2}');
INSERT INTO pool_schema.audit_log VALUES (572, 'pool_schema.subscription_allowed_days', '59', 'INSERT', 1, '2025-11-09 09:09:20.960988+01', NULL, '{"weekday_id": 1, "subscription_id": 59}');
INSERT INTO pool_schema.audit_log VALUES (573, 'pool_schema.subscription_allowed_days', '59', 'INSERT', 1, '2025-11-09 09:09:20.960988+01', NULL, '{"weekday_id": 4, "subscription_id": 59}');
INSERT INTO pool_schema.audit_log VALUES (574, 'pool_schema.access_badges', '30', 'DELETE', 1, '2025-11-09 09:09:30.018837+01', '{"status": "active", "badge_id": 21, "badge_uid": "UID-2004", "issued_at": "2025-10-24T23:39:34.854398+01:00", "member_id": 30, "expires_at": null}', NULL);
INSERT INTO pool_schema.audit_log VALUES (575, 'pool_schema.subscriptions', '30', 'DELETE', 1, '2025-11-09 09:09:30.039963+01', '{"status": "active", "plan_id": 2, "end_date": "2025-11-15", "member_id": 30, "paused_at": null, "created_at": "2025-11-09T09:09:20.960988+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-11-09", "updated_at": "2025-11-09T09:09:20+01:00", "updated_by": null, "activity_id": null, "deactivated_by": null, "subscription_id": 59, "visits_per_week": 2}', NULL);
INSERT INTO pool_schema.audit_log VALUES (576, 'pool_schema.subscription_allowed_days', '59', 'DELETE', 1, '2025-11-09 09:09:30.039963+01', '{"weekday_id": 1, "subscription_id": 59}', NULL);
INSERT INTO pool_schema.audit_log VALUES (577, 'pool_schema.subscription_allowed_days', '59', 'DELETE', 1, '2025-11-09 09:09:30.039963+01', '{"weekday_id": 4, "subscription_id": 59}', NULL);
INSERT INTO pool_schema.audit_log VALUES (578, 'pool_schema.members', '30', 'DELETE', 1, '2025-11-09 09:09:30.052868+01', '{"email": "abdo123@gmail.com", "address": "16000", "last_name": "test", "member_id": 30, "created_at": "2025-11-09T09:09:20.960988+01:00", "created_by": null, "first_name": "Abdo", "updated_at": "2025-11-09T09:09:20.960988+01:00", "updated_by": null, "phone_number": "+21310003258", "date_of_birth": "2025-11-09"}', NULL);
INSERT INTO pool_schema.audit_log VALUES (579, 'pool_schema.subscriptions', '1', 'INSERT', 1, '2025-11-09 09:11:23.421124+01', NULL, '{"status": "active", "plan_id": 1, "end_date": "2025-11-09", "member_id": 1, "paused_at": null, "created_at": "2025-11-09T09:11:23+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-11-09", "updated_at": "2025-11-09T09:11:23+01:00", "updated_by": null, "activity_id": 1, "deactivated_by": null, "subscription_id": 60, "visits_per_week": null}');
INSERT INTO pool_schema.audit_log VALUES (580, 'pool_schema.payments', '60', 'INSERT', 1, '2025-11-09 09:11:23.421124+01', NULL, '{"notes": null, "amount": 40.00, "payment_id": 49, "payment_date": "2025-11-09T09:11:23+01:00", "payment_method": "cash", "subscription_id": 60, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (581, 'pool_schema.payments', '60', 'INSERT', 1, '2025-11-09 09:12:06.870804+01', NULL, '{"notes": null, "amount": 760.00, "payment_id": 50, "payment_date": "2025-11-09T09:12:06+01:00", "payment_method": "cash", "subscription_id": 60, "received_by_staff_id": 1}');
INSERT INTO pool_schema.audit_log VALUES (582, 'pool_schema.subscriptions', '1', 'UPDATE', NULL, '2025-11-11 09:23:29.53982+01', '{"status": "active", "plan_id": 1, "end_date": "2025-11-09", "member_id": 1, "paused_at": null, "created_at": "2025-11-09T09:11:23+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-11-09", "updated_by": null, "activity_id": 1, "deactivated_by": null, "subscription_id": 60, "visits_per_week": null}', '{"status": "expired", "plan_id": 1, "end_date": "2025-11-09", "member_id": 1, "paused_at": null, "created_at": "2025-11-09T09:11:23+01:00", "created_by": null, "resumes_at": null, "start_date": "2025-11-09", "updated_by": null, "activity_id": 1, "deactivated_by": null, "subscription_id": 60, "visits_per_week": null}');


--
-- TOC entry 5449 (class 0 OID 17095)
-- Dependencies: 273
-- Data for Name: audit_settings; Type: TABLE DATA; Schema: pool_schema; Owner: postgres
--

INSERT INTO pool_schema.audit_settings VALUES (1, 90, NULL, '2025-10-26 00:54:43.641593+01');


--
-- TOC entry 5441 (class 0 OID 16970)
-- Dependencies: 265
-- Data for Name: cache; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5442 (class 0 OID 16980)
-- Dependencies: 266
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5428 (class 0 OID 16687)
-- Dependencies: 239
-- Data for Name: facilities; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5447 (class 0 OID 17021)
-- Dependencies: 271
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5445 (class 0 OID 17006)
-- Dependencies: 269
-- Data for Name: job_batches; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5444 (class 0 OID 16991)
-- Dependencies: 268
-- Data for Name: jobs; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5411 (class 0 OID 16485)
-- Dependencies: 222
-- Data for Name: members; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO pool_schema.members VALUES (5, 'user2', 'user2', 'user2@gmail.com', '+213556544474', NULL, NULL, '2025-10-22 20:26:04.475727+01', '2025-10-22 20:26:04.475727+01', NULL, NULL);
INSERT INTO pool_schema.members VALUES (6, 'user3', 'user3', 'user3@gmail.com', '03215478', NULL, NULL, '2025-10-22 20:57:20.696105+01', '2025-10-22 20:57:20.696105+01', NULL, NULL);
INSERT INTO pool_schema.members VALUES (7, 'user4', 'user4', 'user4@gmail.com', '0651245478', NULL, NULL, '2025-10-22 21:17:00.543274+01', '2025-10-22 21:17:00.543274+01', NULL, NULL);
INSERT INTO pool_schema.members VALUES (8, 'user5', 'u5', 'user@gmail.com', '+21310003258', NULL, NULL, '2025-10-22 22:29:44.704433+01', '2025-10-22 22:29:44.704433+01', NULL, NULL);
INSERT INTO pool_schema.members VALUES (9, 'user6', 'u6', 'user6@gmail.com', '+21310003258', NULL, NULL, '2025-10-23 00:13:20.555796+01', '2025-10-23 00:13:20.555796+01', NULL, NULL);
INSERT INTO pool_schema.members VALUES (17, 'u30', 'user30', 'pemoti6884@dxirl.com', '+21310003258', '2021-06-25', 'bouira lakhdaria', '2025-10-25 00:13:58.437296+01', '2025-10-25 00:13:58.437296+01', 1, 1);
INSERT INTO pool_schema.members VALUES (18, 'u40', 'user40', 'user40@gmail.com', '+21310003258', '2013-06-25', 'bouira lakhdaria', '2025-10-25 11:28:55.097094+01', '2025-10-25 11:28:55.097094+01', 1, 1);
INSERT INTO pool_schema.members VALUES (2, 'member 2', 'm2', 'member2@gmail.com', '0654875412', '2025-10-25', '16000', '2025-10-22 14:08:32.694673+01', '2025-10-22 14:08:32.694673+01', NULL, 1);
INSERT INTO pool_schema.members VALUES (21, 'u50', 'user50', 'user50@gmail.com', '03215478', '2025-10-25', 'bouira lakhdaria', '2025-10-25 23:41:19.055895+01', '2025-10-25 23:41:19.055895+01', 1, 1);
INSERT INTO pool_schema.members VALUES (23, 'u70', 'user70', 'user70@gmail.com', '+21310003258', '2025-10-25', 'bouira lakhdaria', '2025-10-25 23:54:38.706317+01', '2025-10-25 23:54:38.706317+01', 1, 1);
INSERT INTO pool_schema.members VALUES (1, 'member1', 'm', 'kohil.abdelhak10@gmail.com', '0777777ddd77', '2025-10-23', '16000', '2025-10-22 01:14:10.659291+01', '2025-10-22 01:14:10.659291+01', NULL, NULL);
INSERT INTO pool_schema.members VALUES (12, 'amine', 'karim', 'user10@gmail.com', '+21310003258', '2004-10-13', NULL, '2025-10-23 00:31:51.715986+01', '2025-10-23 00:31:51.715986+01', 1, NULL);
INSERT INTO pool_schema.members VALUES (3, 'Abd elhak', 'Ka', 'abdo@gmail.com', '+213065847555', '1994-11-02', '16000', '2025-10-22 14:09:21.00445+01', '2025-10-22 14:09:21.00445+01', NULL, NULL);
INSERT INTO pool_schema.members VALUES (13, 'user11', 'u11', 'user11@gmail.com', '02154785', '2025-10-27', NULL, '2025-10-23 00:39:54.984917+01', '2025-10-23 00:39:54.984917+01', 1, NULL);


--
-- TOC entry 5436 (class 0 OID 16925)
-- Dependencies: 260
-- Data for Name: migrations; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO pool_schema.migrations VALUES (1, '0001_01_01_000000_create_users_table', 1);
INSERT INTO pool_schema.migrations VALUES (2, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO pool_schema.migrations VALUES (3, '0001_01_01_000002_create_jobs_table', 1);


--
-- TOC entry 5455 (class 0 OID 17196)
-- Dependencies: 279
-- Data for Name: partner_groups; Type: TABLE DATA; Schema: pool_schema; Owner: postgres
--

INSERT INTO pool_schema.partner_groups VALUES (1, 'École Les Poussins Bleus', 'Mme Nadia Khelifi', '+213 550 223 441', 'nadia.khelifi@poussinsbleus.dz', 'Groupe scolaire maternelle et primaire, cours hebdomadaires de natation.');
INSERT INTO pool_schema.partner_groups VALUES (2, 'Crèche Les Petits Génies', 'M. Karim Boudiaf', '+213 552 441 992', 'karim.boudiaf@petitsgenies.dz', 'Crèche partenaire pour les séances d’initiation aquatique.');
INSERT INTO pool_schema.partner_groups VALUES (3, 'Lycée Privé El Amel', 'Mme Samira Benali', '+213 556 330 128', 'samira.benali@elamel-lycee.dz', 'Séances d’éducation physique deux fois par semaine.');
INSERT INTO pool_schema.partner_groups VALUES (4, 'Entreprise HydroTech', 'M. Rachid Lounis', '+213 661 210 874', 'rachid.lounis@hydrotech.com', 'Programme bien-être pour les employés (aquagym et natation libre).');
INSERT INTO pool_schema.partner_groups VALUES (5, 'Association des Seniors Actifs', 'Mme Fatma Gherbi', '+213 662 442 013', 'fatma.gherbi@seniorsactifs.org', 'Activités aquatiques adaptées pour les seniors.');
INSERT INTO pool_schema.partner_groups VALUES (6, 'Club Nautique Les Dauphins', 'Coach Amine Tahar', '+213 550 773 882', 'amine.tahar@dauphinsclub.dz', 'Partenaire pour les compétitions régionales et les entraînements.');
INSERT INTO pool_schema.partner_groups VALUES (7, 'Centre Médical AquaSanté', 'Dr. Lynda Merabet', '+213 661 998 442', 'lynda.merabet@aquasante.dz', 'Séances de rééducation aquatique supervisées.');
INSERT INTO pool_schema.partner_groups VALUES (8, 'Université Polytech d’Alger', 'Pr. Mourad Zerrouki', '+213 661 871 111', 'mourad.zerrouki@polytech-alger.edu.dz', 'Cours de natation pour les étudiants du département sport.');
INSERT INTO pool_schema.partner_groups VALUES (9, 'École Française Internationale', 'Mme Claire Dubois', '+213 550 445 781', 'claire.dubois@efi-dz.com', 'Séances scolaires encadrées tous les jeudis matins.');
INSERT INTO pool_schema.partner_groups VALUES (10, 'Société GlobalTech', 'M. Ahmed Hamdani', '+213 661 772 334', 'ahmed.hamdani@globaltech.dz', 'Offre de bien-être entreprise : aquagym et relaxation.');
INSERT INTO pool_schema.partner_groups VALUES (11, 'Crèche Les Merveilles', 'Mme Rania Bouzid', '+213 664 559 118', 'rania.bouzid@lesmerveilles.dz', 'Groupe partenaire pour l’initiation aquatique des enfants.');
INSERT INTO pool_schema.partner_groups VALUES (12, 'Association Espoir', 'M. Khaled Aït Saïd', '+213 550 119 412', 'khaled.aitsaid@espoir-dz.org', 'Sessions spéciales pour personnes à mobilité réduite.');
INSERT INTO pool_schema.partner_groups VALUES (13, 'Académie de Natation AquaPro', 'Coach Hichem Ziani', '+213 551 225 876', 'hichem.ziani@aquaproacademy.dz', 'Partenaire pour entraînement technique et compétitions.');
INSERT INTO pool_schema.partner_groups VALUES (14, 'Entreprise Sonatra', 'Mme Lina Mahrez', '+213 661 400 987', 'lina.mahrez@sonatra.com', 'Programme bien-être et santé pour le personnel.');
INSERT INTO pool_schema.partner_groups VALUES (15, 'Collège Ibn Sina', 'M. Farid Zerguine', '+213 662 227 555', 'farid.zerguine@ibnsina.edu.dz', 'Cours d’éducation physique aquatique tous les mercredis.');


--
-- TOC entry 5439 (class 0 OID 16949)
-- Dependencies: 263
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5426 (class 0 OID 16659)
-- Dependencies: 237
-- Data for Name: payments; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO pool_schema.payments VALUES (4, 24, 800.00, '2025-10-25 12:24:16+01', 'cash', 1, 'paye  cach');
INSERT INTO pool_schema.payments VALUES (6, 26, 800.00, '2025-10-25 15:34:23+01', 'card', 1, 'carte');
INSERT INTO pool_schema.payments VALUES (5, 23, 3500.00, '2025-10-25 15:34:37+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (7, 29, 4500.00, '2025-10-25 16:04:49+01', 'cash', 1, 'cash');
INSERT INTO pool_schema.payments VALUES (9, 26, 2700.00, '2025-10-25 17:42:44+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (11, 22, 1000.00, '2025-10-25 19:36:23+01', 'cash', 1, 'cash');
INSERT INTO pool_schema.payments VALUES (12, 22, 1000.00, '2025-10-25 19:36:31+01', 'cash', 1, 'cash');
INSERT INTO pool_schema.payments VALUES (13, 22, 1000.00, '2025-10-25 19:36:35+01', 'cash', 1, 'cash');
INSERT INTO pool_schema.payments VALUES (14, 22, 50.00, '2025-10-25 19:36:47+01', 'cash', 1, 'cash');
INSERT INTO pool_schema.payments VALUES (15, 22, 450.00, '2025-10-25 19:36:53+01', 'cash', 1, 'cash');
INSERT INTO pool_schema.payments VALUES (16, 28, 100.00, '2025-10-25 19:38:48+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (17, 28, 700.00, '2025-10-25 19:39:13+01', 'cash', 1, 'cash');
INSERT INTO pool_schema.payments VALUES (18, 34, 200.00, '2025-10-26 00:27:38+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (19, 36, 1000.00, '2025-10-26 00:55:27+01', 'cash', 1, 'cash');
INSERT INTO pool_schema.payments VALUES (20, 36, 1000.00, '2025-10-26 02:29:41+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (21, 36, 1500.00, '2025-10-26 02:44:25+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (22, 2, 200.00, '2025-10-26 15:49:29+01', 'cash', 5, NULL);
INSERT INTO pool_schema.payments VALUES (23, 2, 600.00, '2025-10-26 15:49:42+01', 'transfer', 5, NULL);
INSERT INTO pool_schema.payments VALUES (24, 40, 800.00, '2025-10-26 16:59:58+01', 'cash', 1, 'especes');
INSERT INTO pool_schema.payments VALUES (25, 41, 1000.00, '2025-10-26 17:01:04+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (26, 41, 2000.00, '2025-10-26 17:01:34+01', 'cash', 1, 'fff');
INSERT INTO pool_schema.payments VALUES (27, 41, 500.00, '2025-10-26 17:01:49+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (28, 29, 100.00, '2025-10-26 17:05:46+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (29, 29, 3500.00, '2025-10-27 23:26:25+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (30, 36, 500.00, '2025-10-27 23:53:47+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (31, 27, 800.00, '2025-10-27 23:54:48+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (32, 12, 4000.00, '2025-10-27 23:56:25+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (33, 34, 330.00, '2025-10-28 00:01:27+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (34, 18, 800.00, '2025-10-28 00:06:05+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (35, 25, 770.00, '2025-10-28 00:09:24+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (36, 25, 30.00, '2025-10-28 00:20:09+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (37, 30, 800.00, '2025-10-28 00:21:06+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (38, 32, 800.00, '2025-10-28 00:28:23+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (39, 9, 3500.00, '2025-10-28 00:31:59+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (40, 42, 4000.00, '2025-10-31 16:29:53+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (41, 42, 500.00, '2025-10-31 16:39:42+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (42, 43, 1000.00, '2025-10-31 16:58:03+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (43, 44, 500.00, '2025-10-31 16:59:52+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (44, 44, 20.00, '2025-10-31 17:43:51+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (45, 44, 680.00, '2025-10-31 17:44:31+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (46, 56, 10.00, '2025-10-31 18:10:50+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (47, 57, 15.00, '2025-10-31 18:11:29+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (48, 58, 3500.00, '2025-11-02 00:31:20+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (49, 60, 40.00, '2025-11-09 09:11:23+01', 'cash', 1, NULL);
INSERT INTO pool_schema.payments VALUES (50, 60, 760.00, '2025-11-09 09:12:06+01', 'cash', 1, NULL);


--
-- TOC entry 5415 (class 0 OID 16514)
-- Dependencies: 226
-- Data for Name: permissions; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO pool_schema.permissions VALUES (2, 'permis2');
INSERT INTO pool_schema.permissions VALUES (4, 'gggggggggggggggg');


--
-- TOC entry 5420 (class 0 OID 16589)
-- Dependencies: 231
-- Data for Name: plans; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO pool_schema.plans VALUES (1, 'natation par visite', NULL, 800.00, 'per_visit', NULL, NULL, true);
INSERT INTO pool_schema.plans VALUES (2, 'natation 2 fois par semaine', NULL, 4000.00, 'monthly_weekly', 2, 1, true);
INSERT INTO pool_schema.plans VALUES (3, 'natation 1 fois par semaine', NULL, 3500.00, 'monthly_weekly', 1, 1, true);


--
-- TOC entry 5457 (class 0 OID 17209)
-- Dependencies: 281
-- Data for Name: reservations; Type: TABLE DATA; Schema: pool_schema; Owner: postgres
--



--
-- TOC entry 5416 (class 0 OID 16524)
-- Dependencies: 227
-- Data for Name: role_permissions; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5413 (class 0 OID 16503)
-- Dependencies: 224
-- Data for Name: roles; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO pool_schema.roles VALUES (1, 'Admin');
INSERT INTO pool_schema.roles VALUES (2, 'directeur');
INSERT INTO pool_schema.roles VALUES (3, 'financer');
INSERT INTO pool_schema.roles VALUES (4, 'maintenances');
INSERT INTO pool_schema.roles VALUES (5, 'receptionniste');


--
-- TOC entry 5440 (class 0 OID 16958)
-- Dependencies: 264
-- Data for Name: sessions; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO pool_schema.sessions VALUES ('AL5kU5zlSFNmxqKOG2pueS0QyFld4XVCXveGnJmY', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiV2pBamUySE1vMmR6bXNpb09ZQm12TWNKczdFZHAwZzJ1WUdNOHQ5TSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozNToiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2FkbWluL21lbWJlcnMiO31zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozNjoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2FkbWluL3BheW1lbnRzIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9', 1762675973);


--
-- TOC entry 5418 (class 0 OID 16563)
-- Dependencies: 229
-- Data for Name: staff; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO pool_schema.staff VALUES (1, 'Abdo', 'Ka', 'admin', '$2y$12$LwXyMXpLUdCCITe.oen02.G75EszsMIkFo8S8abnAGtb3/FF6NgDG', 1, true, '2025-10-21 17:41:45+01');
INSERT INTO pool_schema.staff VALUES (4, 'Ali', 'Ben', 'directeur', '$2y$12$bFPzYqdXjtYwTpZaQXSpC.O1ViQIPSBDxoaNPcyVX3XdjqNbbCnZS', 2, true, '2025-10-21 17:57:38+01');
INSERT INTO pool_schema.staff VALUES (8, 'Test', 'User', 'testuser', '$2y$12$ZBKUfiZF54ERbqMhGpStP.lsJ02aTRdY0gldC9vHTZRXOd7mtcssy', 1, true, '2025-10-21 20:53:19.62074+01');
INSERT INTO pool_schema.staff VALUES (12, 'admin2', 'ad', 'admin2', '$2y$12$0MsVhf.bc638Tyqto4kJF.0tpHTUfpdCOn87YXtgFCBUGDY3/NZay', 1, true, '2025-10-22 14:17:38+01');
INSERT INTO pool_schema.staff VALUES (6, 'Mounir', 'Fix', 'maintenance', '$2y$12$zALPn94XAmg0RDgirIG4XOX.8lnROwSd.XxcvNoKt9ixaHzT2i97G', 5, true, '2025-10-21 17:57:39+01');
INSERT INTO pool_schema.staff VALUES (5, 'Sara', 'Fin', 'financer', '$2y$12$4HJ78y7LccKy/42HHwU5buAdwS/wiJbEvLG26LFXOPGlUBn3kachq', 5, true, '2025-10-21 17:57:38+01');
INSERT INTO pool_schema.staff VALUES (13, 'recep', 'recept', 'user1', '$2y$12$LwXyMXpLUdCCITe.oen02.G75EszsMIkFo8S8abnAGtb3/FF6NgDG', 5, true, '2025-10-31 13:52:43+01');


--
-- TOC entry 5424 (class 0 OID 16641)
-- Dependencies: 235
-- Data for Name: subscription_allowed_days; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO pool_schema.subscription_allowed_days VALUES (57, 5);
INSERT INTO pool_schema.subscription_allowed_days VALUES (58, 1);


--
-- TOC entry 5422 (class 0 OID 16606)
-- Dependencies: 233
-- Data for Name: subscriptions; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO pool_schema.subscriptions VALUES (2, 2, 1, '2025-10-20', '2025-10-23', 'paused', NULL, NULL, '2025-10-22 14:22:55.308291+01', NULL, NULL, NULL, NULL, '2025-10-26 15:49:45+01', NULL);
INSERT INTO pool_schema.subscriptions VALUES (25, 17, 1, '2025-10-24', '2025-10-25', 'expired', NULL, NULL, '2025-10-25 00:13:58.437296+01', NULL, NULL, 1, 1, '2025-10-24 23:13:58+01', NULL);
INSERT INTO pool_schema.subscriptions VALUES (26, 18, 3, '2025-10-25', '2025-10-26', 'expired', NULL, NULL, '2025-10-25 11:28:55.097094+01', 1, NULL, 1, 1, '2025-10-25 10:28:55+01', NULL);
INSERT INTO pool_schema.subscriptions VALUES (30, 21, 1, '2025-10-25', '2025-10-25', 'expired', NULL, NULL, '2025-10-25 23:41:19.055895+01', NULL, NULL, 1, 1, '2025-10-25 22:41:19+01', NULL);
INSERT INTO pool_schema.subscriptions VALUES (44, 12, 1, '2025-10-31', '2025-10-31', 'expired', NULL, NULL, '2025-10-31 16:59:52+01', NULL, NULL, NULL, NULL, '2025-10-31 17:44:35+01', 5);
INSERT INTO pool_schema.subscriptions VALUES (32, 23, 1, '2025-10-25', '2025-10-25', 'expired', NULL, NULL, '2025-10-25 23:54:38.706317+01', NULL, NULL, 1, 1, '2025-10-25 22:54:38+01', NULL);
INSERT INTO pool_schema.subscriptions VALUES (23, 2, 3, '2025-10-24', '2025-10-24', 'paused', NULL, NULL, '2025-10-24 17:43:57.989165+01', 1, NULL, 1, 1, '2025-10-26 00:45:25+01', NULL);
INSERT INTO pool_schema.subscriptions VALUES (56, 12, 2, '2025-11-01', '2025-11-08', 'expired', NULL, NULL, '2025-10-31 18:10:50+01', 2, NULL, NULL, NULL, '2025-10-31 18:10:50+01', 1);
INSERT INTO pool_schema.subscriptions VALUES (57, 12, 3, '2025-10-30', '2025-11-07', 'expired', NULL, NULL, '2025-10-31 18:11:29+01', 1, NULL, NULL, NULL, '2025-11-01 21:59:03+01', 1);
INSERT INTO pool_schema.subscriptions VALUES (29, 3, 2, '2025-10-25', '2025-11-26', 'cancelled', NULL, NULL, '2025-10-25 16:04:49+01', 2, NULL, NULL, 1, '2025-11-09 09:08:20+01', NULL);
INSERT INTO pool_schema.subscriptions VALUES (36, 3, 2, '2025-10-26', '2025-11-26', 'active', NULL, NULL, '2025-10-26 00:55:27+01', 2, NULL, NULL, NULL, '2025-11-09 09:08:20+01', NULL);
INSERT INTO pool_schema.subscriptions VALUES (41, 3, 3, '2025-10-26', '2025-11-26', 'active', NULL, NULL, '2025-10-26 17:01:04+01', 1, NULL, NULL, NULL, '2025-11-09 09:08:20+01', NULL);
INSERT INTO pool_schema.subscriptions VALUES (22, 3, 3, '2025-10-22', '2025-10-26', 'expired', NULL, NULL, '2025-10-24 17:37:39.454325+01', 1, NULL, 1, 1, '2025-11-09 09:08:20+01', NULL);
INSERT INTO pool_schema.subscriptions VALUES (27, 3, 1, '2025-10-25', '2025-10-25', 'expired', NULL, NULL, '2025-10-25 16:57:31.310217+01', NULL, NULL, 1, 1, '2025-11-09 09:08:20+01', NULL);
INSERT INTO pool_schema.subscriptions VALUES (43, 3, 1, '2025-10-31', '2025-10-31', 'expired', NULL, NULL, '2025-10-31 16:58:03+01', NULL, NULL, NULL, NULL, '2025-11-09 09:08:20+01', 5);
INSERT INTO pool_schema.subscriptions VALUES (24, 3, 1, '2025-10-24', '2025-10-25', 'expired', NULL, NULL, '2025-10-24 18:31:02.837957+01', NULL, NULL, 1, 1, '2025-11-09 09:08:20+01', NULL);
INSERT INTO pool_schema.subscriptions VALUES (28, 3, 1, '2025-10-26', '2025-10-26', 'expired', NULL, NULL, '2025-10-25 16:59:14.435357+01', NULL, NULL, 1, 1, '2025-11-09 09:08:20+01', NULL);
INSERT INTO pool_schema.subscriptions VALUES (58, 3, 3, '2025-11-02', '2025-12-02', 'active', NULL, NULL, '2025-11-02 00:31:20+01', 1, NULL, NULL, NULL, '2025-11-09 09:08:20+01', 1);
INSERT INTO pool_schema.subscriptions VALUES (34, 3, 3, '2025-10-26', '2025-10-26', 'expired', NULL, NULL, '2025-10-26 00:27:38+01', 1, NULL, NULL, 1, '2025-11-09 09:08:20+01', NULL);
INSERT INTO pool_schema.subscriptions VALUES (42, 3, 2, '2025-10-31', '2025-11-30', 'active', NULL, NULL, '2025-10-31 16:29:53+01', 2, NULL, NULL, NULL, '2025-11-09 09:08:20+01', 1);
INSERT INTO pool_schema.subscriptions VALUES (60, 1, 1, '2025-11-09', '2025-11-09', 'expired', NULL, NULL, '2025-11-09 09:11:23+01', NULL, NULL, NULL, NULL, '2025-11-09 09:12:10+01', 1);
INSERT INTO pool_schema.subscriptions VALUES (13, 8, 3, '2025-10-22', '2025-10-22', 'expired', NULL, NULL, '2025-10-22 23:40:16.086055+01', 1, NULL, 1, 1, '2025-10-24 23:46:17.487819+01', NULL);
INSERT INTO pool_schema.subscriptions VALUES (12, 8, 2, '2025-10-22', '2025-11-22', 'paused', NULL, NULL, '2025-10-22 22:29:44.704433+01', 2, NULL, 1, 1, '2025-10-24 23:46:17.487819+01', NULL);
INSERT INTO pool_schema.subscriptions VALUES (40, 12, 1, '2025-10-26', '2025-10-26', 'cancelled', NULL, NULL, '2025-10-26 16:59:58+01', NULL, NULL, NULL, NULL, '2025-10-28 00:44:45+01', NULL);
INSERT INTO pool_schema.subscriptions VALUES (9, 5, 3, '2025-10-22', '2025-10-22', 'expired', NULL, NULL, '2025-10-22 20:26:04.475727+01', 1, NULL, 1, 1, '2025-10-28 00:45:17+01', NULL);
INSERT INTO pool_schema.subscriptions VALUES (10, 6, 3, '2025-10-22', '2025-10-22', 'cancelled', NULL, NULL, '2025-10-22 20:57:20.696105+01', 1, NULL, 1, 1, '2025-10-28 00:45:30+01', NULL);
INSERT INTO pool_schema.subscriptions VALUES (18, 13, 1, '2025-10-23', '2025-10-23', 'expired', NULL, NULL, '2025-10-23 00:39:54.984917+01', NULL, NULL, 1, 1, '2025-10-28 00:29:19+01', NULL);


--
-- TOC entry 5453 (class 0 OID 17170)
-- Dependencies: 277
-- Data for Name: time_slots; Type: TABLE DATA; Schema: pool_schema; Owner: postgres
--

INSERT INTO pool_schema.time_slots VALUES (114, 1, '09:30:00', '11:00:00', 4, NULL, false, NULL, NULL, '2025-11-02 00:13:30.515289+01', '2025-11-02 00:13:30.515289+01');
INSERT INTO pool_schema.time_slots VALUES (115, 1, '11:00:00', '12:30:00', 2, NULL, false, NULL, NULL, '2025-11-02 00:13:45.718845+01', '2025-11-02 00:13:45.718845+01');
INSERT INTO pool_schema.time_slots VALUES (116, 1, '12:30:00', '14:00:00', 3, NULL, false, NULL, NULL, '2025-11-02 00:13:52.570357+01', '2025-11-02 00:13:52.570357+01');
INSERT INTO pool_schema.time_slots VALUES (117, 1, '14:00:00', '15:30:00', 8, NULL, false, NULL, NULL, '2025-11-02 00:18:56.484322+01', '2025-11-02 00:18:56.484322+01');
INSERT INTO pool_schema.time_slots VALUES (118, 1, '15:30:00', '17:00:00', 9, NULL, false, NULL, NULL, '2025-11-02 00:19:10.075647+01', '2025-11-02 00:19:10.075647+01');
INSERT INTO pool_schema.time_slots VALUES (119, 1, '17:00:00', '18:30:00', 8, NULL, false, NULL, NULL, '2025-11-02 00:19:27.102307+01', '2025-11-02 00:19:27.102307+01');
INSERT INTO pool_schema.time_slots VALUES (120, 1, '18:30:00', '20:00:00', 1, NULL, false, NULL, NULL, '2025-11-02 00:19:42.695058+01', '2025-11-02 00:19:42.695058+01');
INSERT INTO pool_schema.time_slots VALUES (166, 7, '18:30:00', '20:00:00', 1, NULL, false, NULL, NULL, '2025-11-02 00:29:54.553397+01', '2025-11-02 00:29:54.553397+01');
INSERT INTO pool_schema.time_slots VALUES (167, 1, '08:00:00', '08:30:00', 3, NULL, false, 'hghg', NULL, '2025-11-05 22:16:08.049031+01', '2025-11-05 22:16:08.049031+01');
INSERT INTO pool_schema.time_slots VALUES (121, 2, '08:00:00', '10:00:00', 9, NULL, false, NULL, NULL, '2025-11-02 00:20:03.876495+01', '2025-11-02 00:20:03.876495+01');
INSERT INTO pool_schema.time_slots VALUES (122, 2, '10:00:00', '11:30:00', 4, NULL, false, NULL, NULL, '2025-11-02 00:20:24.617525+01', '2025-11-02 00:20:24.617525+01');
INSERT INTO pool_schema.time_slots VALUES (123, 2, '11:30:00', '13:00:00', 4, NULL, false, NULL, NULL, '2025-11-02 00:21:00.61843+01', '2025-11-02 00:21:00.61843+01');
INSERT INTO pool_schema.time_slots VALUES (124, 2, '13:00:00', '14:30:00', 8, NULL, false, NULL, NULL, '2025-11-02 00:21:16.572891+01', '2025-11-02 00:21:16.572891+01');
INSERT INTO pool_schema.time_slots VALUES (125, 2, '14:30:00', '16:00:00', 8, NULL, false, NULL, NULL, '2025-11-02 00:21:25.419787+01', '2025-11-02 00:21:25.419787+01');
INSERT INTO pool_schema.time_slots VALUES (126, 2, '16:00:00', '17:30:00', 8, NULL, false, NULL, NULL, '2025-11-02 00:21:32.566864+01', '2025-11-02 00:21:32.566864+01');
INSERT INTO pool_schema.time_slots VALUES (127, 2, '17:30:00', '19:00:00', 8, NULL, false, NULL, NULL, '2025-11-02 00:21:47.44006+01', '2025-11-02 00:21:47.44006+01');
INSERT INTO pool_schema.time_slots VALUES (128, 2, '19:00:00', '20:30:00', 1, NULL, false, NULL, NULL, '2025-11-02 00:22:04.542063+01', '2025-11-02 00:22:04.542063+01');
INSERT INTO pool_schema.time_slots VALUES (137, 5, '08:00:00', '09:30:00', 8, NULL, false, NULL, NULL, '2025-11-02 00:24:01.222938+01', '2025-11-02 00:24:01.222938+01');
INSERT INTO pool_schema.time_slots VALUES (138, 5, '09:30:00', '11:00:00', 8, NULL, false, NULL, NULL, '2025-11-02 00:24:09.409638+01', '2025-11-02 00:24:09.409638+01');
INSERT INTO pool_schema.time_slots VALUES (139, 5, '11:00:00', '12:30:00', 8, NULL, false, NULL, NULL, '2025-11-02 00:24:18.238181+01', '2025-11-02 00:24:18.238181+01');
INSERT INTO pool_schema.time_slots VALUES (140, 5, '12:30:00', '14:00:00', 8, NULL, false, NULL, NULL, '2025-11-02 00:24:30.502614+01', '2025-11-02 00:24:30.502614+01');
INSERT INTO pool_schema.time_slots VALUES (141, 5, '14:00:00', '15:30:00', 8, NULL, false, NULL, NULL, '2025-11-02 00:24:37.975939+01', '2025-11-02 00:24:37.975939+01');
INSERT INTO pool_schema.time_slots VALUES (142, 5, '15:30:00', '17:00:00', 8, NULL, false, NULL, NULL, '2025-11-02 00:24:44.098794+01', '2025-11-02 00:24:44.098794+01');
INSERT INTO pool_schema.time_slots VALUES (143, 5, '17:00:00', '18:30:00', 8, NULL, false, NULL, NULL, '2025-11-02 00:24:50.178138+01', '2025-11-02 00:24:50.178138+01');
INSERT INTO pool_schema.time_slots VALUES (144, 5, '18:30:00', '20:00:00', 8, NULL, false, NULL, NULL, '2025-11-02 00:25:17.614677+01', '2025-11-02 00:25:17.614677+01');
INSERT INTO pool_schema.time_slots VALUES (129, 4, '08:00:00', '09:30:00', 4, NULL, false, NULL, NULL, '2025-11-02 00:22:15.654059+01', '2025-11-02 00:22:15.654059+01');
INSERT INTO pool_schema.time_slots VALUES (130, 4, '09:30:00', '11:00:00', 4, NULL, false, NULL, NULL, '2025-11-02 00:22:23.508682+01', '2025-11-02 00:22:23.508682+01');
INSERT INTO pool_schema.time_slots VALUES (131, 4, '11:00:00', '12:30:00', 4, NULL, false, NULL, NULL, '2025-11-02 00:22:31.15615+01', '2025-11-02 00:22:31.15615+01');
INSERT INTO pool_schema.time_slots VALUES (132, 4, '12:30:00', '14:00:00', 4, NULL, false, NULL, NULL, '2025-11-02 00:22:43.039893+01', '2025-11-02 00:22:43.039893+01');
INSERT INTO pool_schema.time_slots VALUES (133, 4, '14:00:00', '15:30:00', 1, NULL, false, NULL, NULL, '2025-11-02 00:22:54.997352+01', '2025-11-02 00:22:54.997352+01');
INSERT INTO pool_schema.time_slots VALUES (134, 4, '15:30:00', '17:00:00', 9, NULL, false, NULL, NULL, '2025-11-02 00:23:03.867479+01', '2025-11-02 00:23:03.867479+01');
INSERT INTO pool_schema.time_slots VALUES (135, 4, '17:00:00', '18:30:00', 8, NULL, false, NULL, NULL, '2025-11-02 00:23:19.656165+01', '2025-11-02 00:23:19.656165+01');
INSERT INTO pool_schema.time_slots VALUES (136, 4, '18:30:00', '20:00:00', 3, NULL, false, NULL, NULL, '2025-11-02 00:23:50.074007+01', '2025-11-02 00:23:50.074007+01');
INSERT INTO pool_schema.time_slots VALUES (145, 3, '08:00:00', '09:30:00', 4, NULL, false, NULL, NULL, '2025-11-02 00:26:32.531657+01', '2025-11-02 00:26:32.531657+01');
INSERT INTO pool_schema.time_slots VALUES (146, 3, '09:30:00', '11:00:00', 2, NULL, false, NULL, NULL, '2025-11-02 00:26:39.24564+01', '2025-11-02 00:26:39.24564+01');
INSERT INTO pool_schema.time_slots VALUES (147, 3, '11:00:00', '12:30:00', 2, NULL, false, NULL, NULL, '2025-11-02 00:26:44.127003+01', '2025-11-02 00:26:44.127003+01');
INSERT INTO pool_schema.time_slots VALUES (148, 3, '12:30:00', '14:00:00', 9, NULL, false, NULL, NULL, '2025-11-02 00:26:50.610416+01', '2025-11-02 00:26:50.610416+01');
INSERT INTO pool_schema.time_slots VALUES (149, 3, '14:00:00', '15:30:00', 5, NULL, false, NULL, NULL, '2025-11-02 00:27:02.057282+01', '2025-11-02 00:27:02.057282+01');
INSERT INTO pool_schema.time_slots VALUES (150, 3, '15:30:00', '17:00:00', 9, NULL, false, NULL, NULL, '2025-11-02 00:27:12.602192+01', '2025-11-02 00:27:12.602192+01');
INSERT INTO pool_schema.time_slots VALUES (151, 3, '17:00:00', '18:30:00', 8, NULL, false, NULL, NULL, '2025-11-02 00:27:26.053472+01', '2025-11-02 00:27:26.053472+01');
INSERT INTO pool_schema.time_slots VALUES (152, 3, '18:30:00', '20:00:00', 1, NULL, false, NULL, NULL, '2025-11-02 00:27:36.559691+01', '2025-11-02 00:27:36.559691+01');
INSERT INTO pool_schema.time_slots VALUES (153, 6, '08:00:00', '09:30:00', 1, NULL, false, NULL, NULL, '2025-11-02 00:27:46.677312+01', '2025-11-02 00:27:46.677312+01');
INSERT INTO pool_schema.time_slots VALUES (154, 6, '09:30:00', '11:00:00', 1, NULL, false, NULL, NULL, '2025-11-02 00:27:53.717448+01', '2025-11-02 00:27:53.717448+01');
INSERT INTO pool_schema.time_slots VALUES (155, 6, '11:00:00', '12:30:00', 2, NULL, false, NULL, NULL, '2025-11-02 00:28:01.81711+01', '2025-11-02 00:28:01.81711+01');
INSERT INTO pool_schema.time_slots VALUES (156, 6, '12:30:00', '14:00:00', 2, NULL, false, NULL, NULL, '2025-11-02 00:28:07.713651+01', '2025-11-02 00:28:07.713651+01');
INSERT INTO pool_schema.time_slots VALUES (157, 6, '14:00:00', '15:30:00', 8, NULL, false, NULL, NULL, '2025-11-02 00:28:17.621727+01', '2025-11-02 00:28:17.621727+01');
INSERT INTO pool_schema.time_slots VALUES (158, 6, '15:30:00', '17:00:00', 8, NULL, false, NULL, NULL, '2025-11-02 00:28:26.099548+01', '2025-11-02 00:28:26.099548+01');
INSERT INTO pool_schema.time_slots VALUES (159, 6, '17:00:00', '18:30:00', 8, NULL, false, NULL, NULL, '2025-11-02 00:28:34.943812+01', '2025-11-02 00:28:34.943812+01');
INSERT INTO pool_schema.time_slots VALUES (160, 6, '18:30:00', '20:00:00', 1, NULL, false, NULL, NULL, '2025-11-02 00:28:41.395616+01', '2025-11-02 00:28:41.395616+01');
INSERT INTO pool_schema.time_slots VALUES (161, 7, '08:00:00', '12:30:00', 6, NULL, false, NULL, NULL, '2025-11-02 00:29:03.31727+01', '2025-11-02 00:29:03.31727+01');
INSERT INTO pool_schema.time_slots VALUES (162, 7, '12:30:00', '14:00:00', 5, NULL, false, NULL, NULL, '2025-11-02 00:29:18.099424+01', '2025-11-02 00:29:18.099424+01');
INSERT INTO pool_schema.time_slots VALUES (163, 7, '14:00:00', '15:30:00', 1, NULL, false, NULL, NULL, '2025-11-02 00:29:27.128687+01', '2025-11-02 00:29:27.128687+01');
INSERT INTO pool_schema.time_slots VALUES (164, 7, '15:30:00', '17:00:00', 9, NULL, false, NULL, NULL, '2025-11-02 00:29:36.197874+01', '2025-11-02 00:29:36.197874+01');
INSERT INTO pool_schema.time_slots VALUES (165, 7, '17:00:00', '18:30:00', 8, NULL, false, NULL, NULL, '2025-11-02 00:29:46.990115+01', '2025-11-02 00:29:46.990115+01');
INSERT INTO pool_schema.time_slots VALUES (168, 1, '08:30:00', '09:30:00', 6, NULL, false, NULL, NULL, '2025-11-05 22:16:30.43808+01', '2025-11-05 22:16:30.43808+01');


--
-- TOC entry 5438 (class 0 OID 16935)
-- Dependencies: 262
-- Data for Name: users; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--



--
-- TOC entry 5423 (class 0 OID 16632)
-- Dependencies: 234
-- Data for Name: weekdays; Type: TABLE DATA; Schema: pool_schema; Owner: pooladmin
--

INSERT INTO pool_schema.weekdays VALUES (1, 'Lundi');
INSERT INTO pool_schema.weekdays VALUES (2, 'Mardi');
INSERT INTO pool_schema.weekdays VALUES (3, 'Mercredi');
INSERT INTO pool_schema.weekdays VALUES (4, 'Jeudi');
INSERT INTO pool_schema.weekdays VALUES (5, 'Vendredi');
INSERT INTO pool_schema.weekdays VALUES (7, 'Dimanche');
INSERT INTO pool_schema.weekdays VALUES (6, 'Samedi');


--
-- TOC entry 5489 (class 0 OID 0)
-- Dependencies: 240
-- Name: access_badges_badge_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('pool_schema.access_badges_badge_id_seq', 40, true);


--
-- TOC entry 5490 (class 0 OID 0)
-- Dependencies: 242
-- Name: access_logs_log_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('pool_schema.access_logs_log_id_seq', 269, true);


--
-- TOC entry 5491 (class 0 OID 0)
-- Dependencies: 274
-- Name: activities_activity_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: postgres
--

SELECT pg_catalog.setval('pool_schema.activities_activity_id_seq', 9, true);


--
-- TOC entry 5492 (class 0 OID 0)
-- Dependencies: 282
-- Name: activity_plan_prices_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: postgres
--

SELECT pg_catalog.setval('pool_schema.activity_plan_prices_id_seq', 26, true);


--
-- TOC entry 5493 (class 0 OID 0)
-- Dependencies: 244
-- Name: audit_log_log_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('pool_schema.audit_log_log_id_seq', 582, true);


--
-- TOC entry 5494 (class 0 OID 0)
-- Dependencies: 272
-- Name: audit_settings_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: postgres
--

SELECT pg_catalog.setval('pool_schema.audit_settings_id_seq', 1, true);


--
-- TOC entry 5495 (class 0 OID 0)
-- Dependencies: 238
-- Name: facilities_facility_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('pool_schema.facilities_facility_id_seq', 1, false);


--
-- TOC entry 5496 (class 0 OID 0)
-- Dependencies: 270
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('pool_schema.failed_jobs_id_seq', 1, false);


--
-- TOC entry 5497 (class 0 OID 0)
-- Dependencies: 267
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('pool_schema.jobs_id_seq', 1, false);


--
-- TOC entry 5498 (class 0 OID 0)
-- Dependencies: 221
-- Name: members_member_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('pool_schema.members_member_id_seq', 30, true);


--
-- TOC entry 5499 (class 0 OID 0)
-- Dependencies: 259
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('pool_schema.migrations_id_seq', 3, true);


--
-- TOC entry 5500 (class 0 OID 0)
-- Dependencies: 278
-- Name: partner_groups_group_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: postgres
--

SELECT pg_catalog.setval('pool_schema.partner_groups_group_id_seq', 17, true);


--
-- TOC entry 5501 (class 0 OID 0)
-- Dependencies: 236
-- Name: payments_payment_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('pool_schema.payments_payment_id_seq', 50, true);


--
-- TOC entry 5502 (class 0 OID 0)
-- Dependencies: 225
-- Name: permissions_permission_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('pool_schema.permissions_permission_id_seq', 4, true);


--
-- TOC entry 5503 (class 0 OID 0)
-- Dependencies: 230
-- Name: plans_plan_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('pool_schema.plans_plan_id_seq', 3, true);


--
-- TOC entry 5504 (class 0 OID 0)
-- Dependencies: 280
-- Name: reservations_reservation_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: postgres
--

SELECT pg_catalog.setval('pool_schema.reservations_reservation_id_seq', 16, true);


--
-- TOC entry 5505 (class 0 OID 0)
-- Dependencies: 223
-- Name: roles_role_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('pool_schema.roles_role_id_seq', 7, true);


--
-- TOC entry 5506 (class 0 OID 0)
-- Dependencies: 228
-- Name: staff_staff_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('pool_schema.staff_staff_id_seq', 13, true);


--
-- TOC entry 5507 (class 0 OID 0)
-- Dependencies: 232
-- Name: subscriptions_subscription_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('pool_schema.subscriptions_subscription_id_seq', 60, true);


--
-- TOC entry 5508 (class 0 OID 0)
-- Dependencies: 276
-- Name: time_slots_slot_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: postgres
--

SELECT pg_catalog.setval('pool_schema.time_slots_slot_id_seq', 168, true);


--
-- TOC entry 5509 (class 0 OID 0)
-- Dependencies: 261
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: pool_schema; Owner: pooladmin
--

SELECT pg_catalog.setval('pool_schema.users_id_seq', 1, false);


--
-- TOC entry 5174 (class 2606 OID 16717)
-- Name: access_badges access_badges_badge_uid_key; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.access_badges
    ADD CONSTRAINT access_badges_badge_uid_key UNIQUE (badge_uid);


--
-- TOC entry 5176 (class 2606 OID 16715)
-- Name: access_badges access_badges_member_id_key; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.access_badges
    ADD CONSTRAINT access_badges_member_id_key UNIQUE (member_id);


--
-- TOC entry 5178 (class 2606 OID 16713)
-- Name: access_badges access_badges_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.access_badges
    ADD CONSTRAINT access_badges_pkey PRIMARY KEY (badge_id);


--
-- TOC entry 5180 (class 2606 OID 16736)
-- Name: access_logs access_logs_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.access_logs
    ADD CONSTRAINT access_logs_pkey PRIMARY KEY (log_id);


--
-- TOC entry 5213 (class 2606 OID 17168)
-- Name: activities activities_name_key; Type: CONSTRAINT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.activities
    ADD CONSTRAINT activities_name_key UNIQUE (name);


--
-- TOC entry 5215 (class 2606 OID 17166)
-- Name: activities activities_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.activities
    ADD CONSTRAINT activities_pkey PRIMARY KEY (activity_id);


--
-- TOC entry 5225 (class 2606 OID 17252)
-- Name: activity_plan_prices activity_plan_prices_activity_id_plan_id_key; Type: CONSTRAINT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.activity_plan_prices
    ADD CONSTRAINT activity_plan_prices_activity_id_plan_id_key UNIQUE (activity_id, plan_id);


--
-- TOC entry 5227 (class 2606 OID 17250)
-- Name: activity_plan_prices activity_plan_prices_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.activity_plan_prices
    ADD CONSTRAINT activity_plan_prices_pkey PRIMARY KEY (id);


--
-- TOC entry 5184 (class 2606 OID 16762)
-- Name: audit_log audit_log_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.audit_log
    ADD CONSTRAINT audit_log_pkey PRIMARY KEY (log_id);


--
-- TOC entry 5211 (class 2606 OID 17105)
-- Name: audit_settings audit_settings_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.audit_settings
    ADD CONSTRAINT audit_settings_pkey PRIMARY KEY (id);


--
-- TOC entry 5200 (class 2606 OID 16989)
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- TOC entry 5198 (class 2606 OID 16979)
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- TOC entry 5170 (class 2606 OID 16699)
-- Name: facilities facilities_name_key; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.facilities
    ADD CONSTRAINT facilities_name_key UNIQUE (name);


--
-- TOC entry 5172 (class 2606 OID 16697)
-- Name: facilities facilities_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.facilities
    ADD CONSTRAINT facilities_pkey PRIMARY KEY (facility_id);


--
-- TOC entry 5207 (class 2606 OID 17036)
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- TOC entry 5209 (class 2606 OID 17038)
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- TOC entry 5205 (class 2606 OID 17019)
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- TOC entry 5202 (class 2606 OID 17004)
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- TOC entry 5135 (class 2606 OID 16501)
-- Name: members members_email_key; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.members
    ADD CONSTRAINT members_email_key UNIQUE (email);


--
-- TOC entry 5137 (class 2606 OID 16499)
-- Name: members members_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.members
    ADD CONSTRAINT members_pkey PRIMARY KEY (member_id);


--
-- TOC entry 5186 (class 2606 OID 16933)
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- TOC entry 5219 (class 2606 OID 17207)
-- Name: partner_groups partner_groups_name_key; Type: CONSTRAINT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.partner_groups
    ADD CONSTRAINT partner_groups_name_key UNIQUE (name);


--
-- TOC entry 5221 (class 2606 OID 17205)
-- Name: partner_groups partner_groups_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.partner_groups
    ADD CONSTRAINT partner_groups_pkey PRIMARY KEY (group_id);


--
-- TOC entry 5192 (class 2606 OID 16957)
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- TOC entry 5168 (class 2606 OID 16675)
-- Name: payments payments_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.payments
    ADD CONSTRAINT payments_pkey PRIMARY KEY (payment_id);


--
-- TOC entry 5143 (class 2606 OID 16523)
-- Name: permissions permissions_permission_name_key; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.permissions
    ADD CONSTRAINT permissions_permission_name_key UNIQUE (permission_name);


--
-- TOC entry 5145 (class 2606 OID 16521)
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (permission_id);


--
-- TOC entry 5154 (class 2606 OID 16604)
-- Name: plans plans_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.plans
    ADD CONSTRAINT plans_pkey PRIMARY KEY (plan_id);


--
-- TOC entry 5223 (class 2606 OID 17223)
-- Name: reservations reservations_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.reservations
    ADD CONSTRAINT reservations_pkey PRIMARY KEY (reservation_id);


--
-- TOC entry 5147 (class 2606 OID 16530)
-- Name: role_permissions role_permissions_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.role_permissions
    ADD CONSTRAINT role_permissions_pkey PRIMARY KEY (role_id, permission_id);


--
-- TOC entry 5139 (class 2606 OID 16510)
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (role_id);


--
-- TOC entry 5141 (class 2606 OID 16512)
-- Name: roles roles_role_name_key; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.roles
    ADD CONSTRAINT roles_role_name_key UNIQUE (role_name);


--
-- TOC entry 5195 (class 2606 OID 16967)
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- TOC entry 5150 (class 2606 OID 16580)
-- Name: staff staff_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.staff
    ADD CONSTRAINT staff_pkey PRIMARY KEY (staff_id);


--
-- TOC entry 5152 (class 2606 OID 16582)
-- Name: staff staff_username_key; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.staff
    ADD CONSTRAINT staff_username_key UNIQUE (username);


--
-- TOC entry 5164 (class 2606 OID 16647)
-- Name: subscription_allowed_days subscription_allowed_days_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscription_allowed_days
    ADD CONSTRAINT subscription_allowed_days_pkey PRIMARY KEY (subscription_id, weekday_id);


--
-- TOC entry 5158 (class 2606 OID 16621)
-- Name: subscriptions subscriptions_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscriptions
    ADD CONSTRAINT subscriptions_pkey PRIMARY KEY (subscription_id);


--
-- TOC entry 5217 (class 2606 OID 17184)
-- Name: time_slots time_slots_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.time_slots
    ADD CONSTRAINT time_slots_pkey PRIMARY KEY (slot_id);


--
-- TOC entry 5188 (class 2606 OID 16948)
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- TOC entry 5190 (class 2606 OID 16946)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- TOC entry 5160 (class 2606 OID 16640)
-- Name: weekdays weekdays_day_name_key; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.weekdays
    ADD CONSTRAINT weekdays_day_name_key UNIQUE (day_name);


--
-- TOC entry 5162 (class 2606 OID 16638)
-- Name: weekdays weekdays_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.weekdays
    ADD CONSTRAINT weekdays_pkey PRIMARY KEY (weekday_id);


--
-- TOC entry 5181 (class 1259 OID 16748)
-- Name: idx_access_logs_member_id; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_access_logs_member_id ON pool_schema.access_logs USING btree (member_id);


--
-- TOC entry 5182 (class 1259 OID 16743)
-- Name: idx_access_logs_member_time; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_access_logs_member_time ON pool_schema.access_logs USING btree (member_id, access_time DESC);


--
-- TOC entry 5228 (class 1259 OID 17263)
-- Name: idx_activity_plan_prices_activity_id; Type: INDEX; Schema: pool_schema; Owner: postgres
--

CREATE INDEX idx_activity_plan_prices_activity_id ON pool_schema.activity_plan_prices USING btree (activity_id);


--
-- TOC entry 5229 (class 1259 OID 17264)
-- Name: idx_activity_plan_prices_plan_id; Type: INDEX; Schema: pool_schema; Owner: postgres
--

CREATE INDEX idx_activity_plan_prices_plan_id ON pool_schema.activity_plan_prices USING btree (plan_id);


--
-- TOC entry 5165 (class 1259 OID 16747)
-- Name: idx_payments_received_by_staff_id; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_payments_received_by_staff_id ON pool_schema.payments USING btree (received_by_staff_id);


--
-- TOC entry 5166 (class 1259 OID 16746)
-- Name: idx_payments_subscription_id; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_payments_subscription_id ON pool_schema.payments USING btree (subscription_id);


--
-- TOC entry 5148 (class 1259 OID 16744)
-- Name: idx_staff_role_id; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_staff_role_id ON pool_schema.staff USING btree (role_id);


--
-- TOC entry 5155 (class 1259 OID 16742)
-- Name: idx_subscriptions_member_status; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_subscriptions_member_status ON pool_schema.subscriptions USING btree (member_id, status) WHERE (status = 'active'::pool_schema.subscription_status_enum);


--
-- TOC entry 5156 (class 1259 OID 16745)
-- Name: idx_subscriptions_plan_id; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_subscriptions_plan_id ON pool_schema.subscriptions USING btree (plan_id);


--
-- TOC entry 5203 (class 1259 OID 17005)
-- Name: jobs_queue_index; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX jobs_queue_index ON pool_schema.jobs USING btree (queue);


--
-- TOC entry 5193 (class 1259 OID 16969)
-- Name: sessions_last_activity_index; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX sessions_last_activity_index ON pool_schema.sessions USING btree (last_activity);


--
-- TOC entry 5196 (class 1259 OID 16968)
-- Name: sessions_user_id_index; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX sessions_user_id_index ON pool_schema.sessions USING btree (user_id);


--
-- TOC entry 5262 (class 2620 OID 17108)
-- Name: access_badges trg_audit_access_badges; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_access_badges AFTER INSERT OR DELETE OR UPDATE ON pool_schema.access_badges FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5253 (class 2620 OID 17107)
-- Name: members trg_audit_members; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_members AFTER INSERT OR DELETE OR UPDATE ON pool_schema.members FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5261 (class 2620 OID 17110)
-- Name: payments trg_audit_payments; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_payments AFTER INSERT OR DELETE OR UPDATE ON pool_schema.payments FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5255 (class 2620 OID 17113)
-- Name: permissions trg_audit_permission; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_permission AFTER INSERT OR DELETE OR UPDATE ON pool_schema.permissions FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5258 (class 2620 OID 17116)
-- Name: plans trg_audit_plan; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_plan AFTER INSERT OR DELETE OR UPDATE ON pool_schema.plans FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5254 (class 2620 OID 17114)
-- Name: roles trg_audit_role; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_role AFTER INSERT OR DELETE OR UPDATE ON pool_schema.roles FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5256 (class 2620 OID 17115)
-- Name: role_permissions trg_audit_role_permission; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_role_permission AFTER INSERT OR DELETE OR UPDATE ON pool_schema.role_permissions FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5257 (class 2620 OID 17111)
-- Name: staff trg_audit_staff; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_staff AFTER INSERT OR DELETE OR UPDATE ON pool_schema.staff FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5260 (class 2620 OID 17112)
-- Name: subscription_allowed_days trg_audit_subscription_allowed_days; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_subscription_allowed_days AFTER INSERT OR DELETE OR UPDATE ON pool_schema.subscription_allowed_days FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5259 (class 2620 OID 17109)
-- Name: subscriptions trg_audit_subscriptions; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_subscriptions AFTER INSERT OR DELETE OR UPDATE ON pool_schema.subscriptions FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5243 (class 2606 OID 16718)
-- Name: access_badges access_badges_member_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.access_badges
    ADD CONSTRAINT access_badges_member_id_fkey FOREIGN KEY (member_id) REFERENCES pool_schema.members(member_id) ON DELETE CASCADE;


--
-- TOC entry 5244 (class 2606 OID 16737)
-- Name: access_logs access_logs_member_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.access_logs
    ADD CONSTRAINT access_logs_member_id_fkey FOREIGN KEY (member_id) REFERENCES pool_schema.members(member_id);


--
-- TOC entry 5251 (class 2606 OID 17253)
-- Name: activity_plan_prices activity_plan_prices_activity_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.activity_plan_prices
    ADD CONSTRAINT activity_plan_prices_activity_id_fkey FOREIGN KEY (activity_id) REFERENCES pool_schema.activities(activity_id) ON DELETE CASCADE;


--
-- TOC entry 5252 (class 2606 OID 17258)
-- Name: activity_plan_prices activity_plan_prices_plan_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.activity_plan_prices
    ADD CONSTRAINT activity_plan_prices_plan_id_fkey FOREIGN KEY (plan_id) REFERENCES pool_schema.plans(plan_id) ON DELETE CASCADE;


--
-- TOC entry 5245 (class 2606 OID 16763)
-- Name: audit_log audit_log_changed_by_staff_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.audit_log
    ADD CONSTRAINT audit_log_changed_by_staff_id_fkey FOREIGN KEY (changed_by_staff_id) REFERENCES pool_schema.staff(staff_id);


--
-- TOC entry 5241 (class 2606 OID 16681)
-- Name: payments payments_received_by_staff_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.payments
    ADD CONSTRAINT payments_received_by_staff_id_fkey FOREIGN KEY (received_by_staff_id) REFERENCES pool_schema.staff(staff_id);


--
-- TOC entry 5242 (class 2606 OID 16676)
-- Name: payments payments_subscription_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.payments
    ADD CONSTRAINT payments_subscription_id_fkey FOREIGN KEY (subscription_id) REFERENCES pool_schema.subscriptions(subscription_id);


--
-- TOC entry 5248 (class 2606 OID 17229)
-- Name: reservations reservations_member_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.reservations
    ADD CONSTRAINT reservations_member_id_fkey FOREIGN KEY (member_id) REFERENCES pool_schema.members(member_id) ON DELETE SET NULL;


--
-- TOC entry 5249 (class 2606 OID 17234)
-- Name: reservations reservations_partner_group_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.reservations
    ADD CONSTRAINT reservations_partner_group_id_fkey FOREIGN KEY (partner_group_id) REFERENCES pool_schema.partner_groups(group_id) ON DELETE SET NULL;


--
-- TOC entry 5250 (class 2606 OID 17224)
-- Name: reservations reservations_slot_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.reservations
    ADD CONSTRAINT reservations_slot_id_fkey FOREIGN KEY (slot_id) REFERENCES pool_schema.time_slots(slot_id) ON DELETE CASCADE;


--
-- TOC entry 5230 (class 2606 OID 16536)
-- Name: role_permissions role_permissions_permission_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.role_permissions
    ADD CONSTRAINT role_permissions_permission_id_fkey FOREIGN KEY (permission_id) REFERENCES pool_schema.permissions(permission_id) ON DELETE CASCADE;


--
-- TOC entry 5231 (class 2606 OID 16531)
-- Name: role_permissions role_permissions_role_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.role_permissions
    ADD CONSTRAINT role_permissions_role_id_fkey FOREIGN KEY (role_id) REFERENCES pool_schema.roles(role_id) ON DELETE CASCADE;


--
-- TOC entry 5232 (class 2606 OID 16583)
-- Name: staff staff_role_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.staff
    ADD CONSTRAINT staff_role_id_fkey FOREIGN KEY (role_id) REFERENCES pool_schema.roles(role_id);


--
-- TOC entry 5239 (class 2606 OID 16648)
-- Name: subscription_allowed_days subscription_allowed_days_subscription_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscription_allowed_days
    ADD CONSTRAINT subscription_allowed_days_subscription_id_fkey FOREIGN KEY (subscription_id) REFERENCES pool_schema.subscriptions(subscription_id) ON DELETE CASCADE;


--
-- TOC entry 5240 (class 2606 OID 16653)
-- Name: subscription_allowed_days subscription_allowed_days_weekday_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscription_allowed_days
    ADD CONSTRAINT subscription_allowed_days_weekday_id_fkey FOREIGN KEY (weekday_id) REFERENCES pool_schema.weekdays(weekday_id) ON DELETE CASCADE;


--
-- TOC entry 5233 (class 2606 OID 17265)
-- Name: subscriptions subscriptions_activity_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscriptions
    ADD CONSTRAINT subscriptions_activity_id_fkey FOREIGN KEY (activity_id) REFERENCES pool_schema.activities(activity_id);


--
-- TOC entry 5234 (class 2606 OID 17053)
-- Name: subscriptions subscriptions_created_by_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscriptions
    ADD CONSTRAINT subscriptions_created_by_fkey FOREIGN KEY (created_by) REFERENCES pool_schema.staff(staff_id);


--
-- TOC entry 5235 (class 2606 OID 17048)
-- Name: subscriptions subscriptions_deactivated_by_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscriptions
    ADD CONSTRAINT subscriptions_deactivated_by_fkey FOREIGN KEY (deactivated_by) REFERENCES pool_schema.staff(staff_id);


--
-- TOC entry 5236 (class 2606 OID 16622)
-- Name: subscriptions subscriptions_member_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscriptions
    ADD CONSTRAINT subscriptions_member_id_fkey FOREIGN KEY (member_id) REFERENCES pool_schema.members(member_id) ON DELETE CASCADE;


--
-- TOC entry 5237 (class 2606 OID 16627)
-- Name: subscriptions subscriptions_plan_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscriptions
    ADD CONSTRAINT subscriptions_plan_id_fkey FOREIGN KEY (plan_id) REFERENCES pool_schema.plans(plan_id) ON DELETE RESTRICT;


--
-- TOC entry 5238 (class 2606 OID 17058)
-- Name: subscriptions subscriptions_updated_by_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscriptions
    ADD CONSTRAINT subscriptions_updated_by_fkey FOREIGN KEY (updated_by) REFERENCES pool_schema.staff(staff_id);


--
-- TOC entry 5246 (class 2606 OID 17190)
-- Name: time_slots time_slots_activity_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.time_slots
    ADD CONSTRAINT time_slots_activity_id_fkey FOREIGN KEY (activity_id) REFERENCES pool_schema.activities(activity_id) ON DELETE SET NULL;


--
-- TOC entry 5247 (class 2606 OID 17185)
-- Name: time_slots time_slots_weekday_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: postgres
--

ALTER TABLE ONLY pool_schema.time_slots
    ADD CONSTRAINT time_slots_weekday_id_fkey FOREIGN KEY (weekday_id) REFERENCES pool_schema.weekdays(weekday_id);


--
-- TOC entry 5465 (class 0 OID 0)
-- Dependencies: 7
-- Name: SCHEMA pool_schema; Type: ACL; Schema: -; Owner: pooladmin
--

REVOKE ALL ON SCHEMA pool_schema FROM pooladmin;
GRANT ALL ON SCHEMA pool_schema TO pooladmin WITH GRANT OPTION;


-- Completed on 2025-11-24 18:24:30

--
-- PostgreSQL database dump complete
--

\unrestrict 5HPjhRiFy0QR4IYOi7CtnRxoqmIqX3R7Hm11063w6nsReXR9AUbhHRFiN61cxqc


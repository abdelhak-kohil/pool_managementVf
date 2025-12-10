--
-- PostgreSQL database dump
--

\restrict K5ZkHu0YaWxxX4jfhnsZTZqLpea42S5tikbcRJQBXt8ghaYWjrdslqTl6UpB6sd

-- Dumped from database version 18.0
-- Dumped by pg_dump version 18.0

-- Started on 2025-11-28 09:36:27

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

CREATE SCHEMA pool_schema;


ALTER SCHEMA pool_schema OWNER TO pooladmin;

--
-- TOC entry 906 (class 1247 OID 25849)
-- Name: access_decision_enum; Type: TYPE; Schema: pool_schema; Owner: pooladmin
--

CREATE TYPE pool_schema.access_decision_enum AS ENUM (
    'granted',
    'denied'
);


ALTER TYPE pool_schema.access_decision_enum OWNER TO pooladmin;

--
-- TOC entry 909 (class 1247 OID 25854)
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
-- TOC entry 912 (class 1247 OID 25866)
-- Name: facility_status_enum; Type: TYPE; Schema: pool_schema; Owner: pooladmin
--

CREATE TYPE pool_schema.facility_status_enum AS ENUM (
    'operational',
    'under_maintenance',
    'closed'
);


ALTER TYPE pool_schema.facility_status_enum OWNER TO pooladmin;

--
-- TOC entry 915 (class 1247 OID 25874)
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
-- TOC entry 918 (class 1247 OID 25884)
-- Name: plan_type_enum; Type: TYPE; Schema: pool_schema; Owner: pooladmin
--

CREATE TYPE pool_schema.plan_type_enum AS ENUM (
    'monthly_weekly',
    'per_visit'
);


ALTER TYPE pool_schema.plan_type_enum OWNER TO pooladmin;

--
-- TOC entry 921 (class 1247 OID 25890)
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
-- TOC entry 273 (class 1255 OID 25899)
-- Name: fn_audit_log_changes(); Type: FUNCTION; Schema: pool_schema; Owner: pooladmin
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


ALTER FUNCTION pool_schema.fn_audit_log_changes() OWNER TO pooladmin;

--
-- TOC entry 272 (class 1255 OID 25900)
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
-- TOC entry 220 (class 1259 OID 25901)
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
-- TOC entry 221 (class 1259 OID 25910)
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
-- TOC entry 5373 (class 0 OID 0)
-- Dependencies: 221
-- Name: access_badges_badge_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.access_badges_badge_id_seq OWNED BY pool_schema.access_badges.badge_id;


--
-- TOC entry 222 (class 1259 OID 25911)
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
-- TOC entry 223 (class 1259 OID 25921)
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
-- TOC entry 5374 (class 0 OID 0)
-- Dependencies: 223
-- Name: access_logs_log_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.access_logs_log_id_seq OWNED BY pool_schema.access_logs.log_id;


--
-- TOC entry 224 (class 1259 OID 25922)
-- Name: activities; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.activities (
    activity_id bigint NOT NULL,
    name character varying(100) NOT NULL,
    description text,
    access_type character varying(50),
    color_code character varying(20) DEFAULT '#60a5fa'::character varying,
    is_active boolean DEFAULT true NOT NULL
);


ALTER TABLE pool_schema.activities OWNER TO pooladmin;

--
-- TOC entry 225 (class 1259 OID 25932)
-- Name: activities_activity_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.activities_activity_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.activities_activity_id_seq OWNER TO pooladmin;

--
-- TOC entry 5375 (class 0 OID 0)
-- Dependencies: 225
-- Name: activities_activity_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.activities_activity_id_seq OWNED BY pool_schema.activities.activity_id;


--
-- TOC entry 226 (class 1259 OID 25933)
-- Name: activity_plan_prices; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.activity_plan_prices (
    id bigint NOT NULL,
    activity_id bigint NOT NULL,
    plan_id bigint NOT NULL,
    price numeric(10,2) NOT NULL,
    CONSTRAINT activity_plan_prices_price_check CHECK ((price >= (0)::numeric))
);


ALTER TABLE pool_schema.activity_plan_prices OWNER TO pooladmin;

--
-- TOC entry 5376 (class 0 OID 0)
-- Dependencies: 226
-- Name: TABLE activity_plan_prices; Type: COMMENT; Schema: pool_schema; Owner: pooladmin
--

COMMENT ON TABLE pool_schema.activity_plan_prices IS 'Defines specific pricing rules between activities and plans (per activity-plan pair).';


--
-- TOC entry 5377 (class 0 OID 0)
-- Dependencies: 226
-- Name: COLUMN activity_plan_prices.price; Type: COMMENT; Schema: pool_schema; Owner: pooladmin
--

COMMENT ON COLUMN pool_schema.activity_plan_prices.price IS 'Custom price for this combination of activity and plan.';


--
-- TOC entry 227 (class 1259 OID 25941)
-- Name: activity_plan_prices_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.activity_plan_prices_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.activity_plan_prices_id_seq OWNER TO pooladmin;

--
-- TOC entry 5378 (class 0 OID 0)
-- Dependencies: 227
-- Name: activity_plan_prices_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.activity_plan_prices_id_seq OWNED BY pool_schema.activity_plan_prices.id;


--
-- TOC entry 228 (class 1259 OID 25942)
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
-- TOC entry 229 (class 1259 OID 25952)
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
-- TOC entry 5379 (class 0 OID 0)
-- Dependencies: 229
-- Name: audit_log_log_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.audit_log_log_id_seq OWNED BY pool_schema.audit_log.log_id;


--
-- TOC entry 230 (class 1259 OID 25953)
-- Name: audit_settings; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.audit_settings (
    id bigint NOT NULL,
    retention_days integer DEFAULT 90 NOT NULL,
    last_manual_run_at timestamp with time zone,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE pool_schema.audit_settings OWNER TO pooladmin;

--
-- TOC entry 231 (class 1259 OID 25961)
-- Name: audit_settings_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.audit_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.audit_settings_id_seq OWNER TO pooladmin;

--
-- TOC entry 5380 (class 0 OID 0)
-- Dependencies: 231
-- Name: audit_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.audit_settings_id_seq OWNED BY pool_schema.audit_settings.id;


--
-- TOC entry 232 (class 1259 OID 25962)
-- Name: cache; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE pool_schema.cache OWNER TO pooladmin;

--
-- TOC entry 233 (class 1259 OID 25970)
-- Name: cache_locks; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE pool_schema.cache_locks OWNER TO pooladmin;

--
-- TOC entry 234 (class 1259 OID 25978)
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
-- TOC entry 235 (class 1259 OID 25986)
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
-- TOC entry 5381 (class 0 OID 0)
-- Dependencies: 235
-- Name: facilities_facility_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.facilities_facility_id_seq OWNED BY pool_schema.facilities.facility_id;


--
-- TOC entry 236 (class 1259 OID 25987)
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
-- TOC entry 237 (class 1259 OID 26000)
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
-- TOC entry 5382 (class 0 OID 0)
-- Dependencies: 237
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.failed_jobs_id_seq OWNED BY pool_schema.failed_jobs.id;


--
-- TOC entry 238 (class 1259 OID 26001)
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
-- TOC entry 239 (class 1259 OID 26013)
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
-- TOC entry 240 (class 1259 OID 26024)
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
-- TOC entry 5383 (class 0 OID 0)
-- Dependencies: 240
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.jobs_id_seq OWNED BY pool_schema.jobs.id;


--
-- TOC entry 241 (class 1259 OID 26025)
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
-- TOC entry 242 (class 1259 OID 26037)
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
-- TOC entry 5384 (class 0 OID 0)
-- Dependencies: 242
-- Name: members_member_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.members_member_id_seq OWNED BY pool_schema.members.member_id;


--
-- TOC entry 243 (class 1259 OID 26038)
-- Name: migrations; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE pool_schema.migrations OWNER TO pooladmin;

--
-- TOC entry 244 (class 1259 OID 26044)
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
-- TOC entry 5385 (class 0 OID 0)
-- Dependencies: 244
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.migrations_id_seq OWNED BY pool_schema.migrations.id;


--
-- TOC entry 245 (class 1259 OID 26045)
-- Name: partner_groups; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.partner_groups (
    group_id bigint NOT NULL,
    name character varying(150) NOT NULL,
    contact_name character varying(100),
    contact_phone character varying(30),
    email character varying(100),
    notes text
);


ALTER TABLE pool_schema.partner_groups OWNER TO pooladmin;

--
-- TOC entry 246 (class 1259 OID 26052)
-- Name: partner_groups_group_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.partner_groups_group_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.partner_groups_group_id_seq OWNER TO pooladmin;

--
-- TOC entry 5386 (class 0 OID 0)
-- Dependencies: 246
-- Name: partner_groups_group_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.partner_groups_group_id_seq OWNED BY pool_schema.partner_groups.group_id;


--
-- TOC entry 247 (class 1259 OID 26053)
-- Name: password_reset_tokens; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE pool_schema.password_reset_tokens OWNER TO pooladmin;

--
-- TOC entry 248 (class 1259 OID 26060)
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
-- TOC entry 249 (class 1259 OID 26074)
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
-- TOC entry 5387 (class 0 OID 0)
-- Dependencies: 249
-- Name: payments_payment_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.payments_payment_id_seq OWNED BY pool_schema.payments.payment_id;


--
-- TOC entry 250 (class 1259 OID 26075)
-- Name: permissions; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.permissions (
    permission_id bigint NOT NULL,
    permission_name character varying(100) NOT NULL
);


ALTER TABLE pool_schema.permissions OWNER TO pooladmin;

--
-- TOC entry 251 (class 1259 OID 26080)
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
-- TOC entry 5388 (class 0 OID 0)
-- Dependencies: 251
-- Name: permissions_permission_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.permissions_permission_id_seq OWNED BY pool_schema.permissions.permission_id;


--
-- TOC entry 252 (class 1259 OID 26081)
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
-- TOC entry 253 (class 1259 OID 26094)
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
-- TOC entry 5389 (class 0 OID 0)
-- Dependencies: 253
-- Name: plans_plan_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.plans_plan_id_seq OWNED BY pool_schema.plans.plan_id;


--
-- TOC entry 254 (class 1259 OID 26095)
-- Name: reservations; Type: TABLE; Schema: pool_schema; Owner: pooladmin
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
    CONSTRAINT reservations_reservation_type_check CHECK (((reservation_type)::text = ANY (ARRAY[('member_private'::character varying)::text, ('partner_group'::character varying)::text]))),
    CONSTRAINT reservations_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('confirmed'::character varying)::text, ('cancelled'::character varying)::text])))
);


ALTER TABLE pool_schema.reservations OWNER TO pooladmin;

--
-- TOC entry 255 (class 1259 OID 26107)
-- Name: reservations_reservation_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.reservations_reservation_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.reservations_reservation_id_seq OWNER TO pooladmin;

--
-- TOC entry 5390 (class 0 OID 0)
-- Dependencies: 255
-- Name: reservations_reservation_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.reservations_reservation_id_seq OWNED BY pool_schema.reservations.reservation_id;


--
-- TOC entry 256 (class 1259 OID 26108)
-- Name: role_permissions; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.role_permissions (
    role_id bigint NOT NULL,
    permission_id bigint NOT NULL
);


ALTER TABLE pool_schema.role_permissions OWNER TO pooladmin;

--
-- TOC entry 257 (class 1259 OID 26113)
-- Name: roles; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.roles (
    role_id bigint NOT NULL,
    role_name character varying(50) NOT NULL
);


ALTER TABLE pool_schema.roles OWNER TO pooladmin;

--
-- TOC entry 258 (class 1259 OID 26118)
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
-- TOC entry 5391 (class 0 OID 0)
-- Dependencies: 258
-- Name: roles_role_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.roles_role_id_seq OWNED BY pool_schema.roles.role_id;


--
-- TOC entry 259 (class 1259 OID 26119)
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
-- TOC entry 260 (class 1259 OID 26127)
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
-- TOC entry 261 (class 1259 OID 26142)
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
-- TOC entry 5392 (class 0 OID 0)
-- Dependencies: 261
-- Name: staff_staff_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.staff_staff_id_seq OWNED BY pool_schema.staff.staff_id;


--
-- TOC entry 262 (class 1259 OID 26143)
-- Name: subscription_allowed_days; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.subscription_allowed_days (
    subscription_id bigint NOT NULL,
    weekday_id smallint NOT NULL
);


ALTER TABLE pool_schema.subscription_allowed_days OWNER TO pooladmin;

--
-- TOC entry 271 (class 1259 OID 26445)
-- Name: subscription_slots; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.subscription_slots (
    subscription_slot_id integer NOT NULL,
    subscription_id integer NOT NULL,
    slot_id integer NOT NULL,
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone DEFAULT now()
);


ALTER TABLE pool_schema.subscription_slots OWNER TO pooladmin;

--
-- TOC entry 270 (class 1259 OID 26444)
-- Name: subscription_slots_subscription_slot_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.subscription_slots_subscription_slot_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.subscription_slots_subscription_slot_id_seq OWNER TO pooladmin;

--
-- TOC entry 5393 (class 0 OID 0)
-- Dependencies: 270
-- Name: subscription_slots_subscription_slot_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.subscription_slots_subscription_slot_id_seq OWNED BY pool_schema.subscription_slots.subscription_slot_id;


--
-- TOC entry 263 (class 1259 OID 26148)
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
-- TOC entry 264 (class 1259 OID 26163)
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
-- TOC entry 5394 (class 0 OID 0)
-- Dependencies: 264
-- Name: subscriptions_subscription_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.subscriptions_subscription_id_seq OWNED BY pool_schema.subscriptions.subscription_id;


--
-- TOC entry 265 (class 1259 OID 26164)
-- Name: time_slots; Type: TABLE; Schema: pool_schema; Owner: pooladmin
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


ALTER TABLE pool_schema.time_slots OWNER TO pooladmin;

--
-- TOC entry 266 (class 1259 OID 26178)
-- Name: time_slots_slot_id_seq; Type: SEQUENCE; Schema: pool_schema; Owner: pooladmin
--

CREATE SEQUENCE pool_schema.time_slots_slot_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE pool_schema.time_slots_slot_id_seq OWNER TO pooladmin;

--
-- TOC entry 5395 (class 0 OID 0)
-- Dependencies: 266
-- Name: time_slots_slot_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.time_slots_slot_id_seq OWNED BY pool_schema.time_slots.slot_id;


--
-- TOC entry 267 (class 1259 OID 26179)
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
-- TOC entry 268 (class 1259 OID 26188)
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
-- TOC entry 5396 (class 0 OID 0)
-- Dependencies: 268
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: pool_schema; Owner: pooladmin
--

ALTER SEQUENCE pool_schema.users_id_seq OWNED BY pool_schema.users.id;


--
-- TOC entry 269 (class 1259 OID 26189)
-- Name: weekdays; Type: TABLE; Schema: pool_schema; Owner: pooladmin
--

CREATE TABLE pool_schema.weekdays (
    weekday_id smallint NOT NULL,
    day_name character varying(10) NOT NULL
);


ALTER TABLE pool_schema.weekdays OWNER TO pooladmin;

--
-- TOC entry 5014 (class 2604 OID 26194)
-- Name: access_badges badge_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.access_badges ALTER COLUMN badge_id SET DEFAULT nextval('pool_schema.access_badges_badge_id_seq'::regclass);


--
-- TOC entry 5017 (class 2604 OID 26195)
-- Name: access_logs log_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.access_logs ALTER COLUMN log_id SET DEFAULT nextval('pool_schema.access_logs_log_id_seq'::regclass);


--
-- TOC entry 5019 (class 2604 OID 26196)
-- Name: activities activity_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.activities ALTER COLUMN activity_id SET DEFAULT nextval('pool_schema.activities_activity_id_seq'::regclass);


--
-- TOC entry 5022 (class 2604 OID 26197)
-- Name: activity_plan_prices id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.activity_plan_prices ALTER COLUMN id SET DEFAULT nextval('pool_schema.activity_plan_prices_id_seq'::regclass);


--
-- TOC entry 5023 (class 2604 OID 26198)
-- Name: audit_log log_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.audit_log ALTER COLUMN log_id SET DEFAULT nextval('pool_schema.audit_log_log_id_seq'::regclass);


--
-- TOC entry 5025 (class 2604 OID 26199)
-- Name: audit_settings id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.audit_settings ALTER COLUMN id SET DEFAULT nextval('pool_schema.audit_settings_id_seq'::regclass);


--
-- TOC entry 5028 (class 2604 OID 26200)
-- Name: facilities facility_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.facilities ALTER COLUMN facility_id SET DEFAULT nextval('pool_schema.facilities_facility_id_seq'::regclass);


--
-- TOC entry 5030 (class 2604 OID 26201)
-- Name: failed_jobs id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.failed_jobs ALTER COLUMN id SET DEFAULT nextval('pool_schema.failed_jobs_id_seq'::regclass);


--
-- TOC entry 5032 (class 2604 OID 26202)
-- Name: jobs id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.jobs ALTER COLUMN id SET DEFAULT nextval('pool_schema.jobs_id_seq'::regclass);


--
-- TOC entry 5033 (class 2604 OID 26203)
-- Name: members member_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.members ALTER COLUMN member_id SET DEFAULT nextval('pool_schema.members_member_id_seq'::regclass);


--
-- TOC entry 5036 (class 2604 OID 26204)
-- Name: migrations id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.migrations ALTER COLUMN id SET DEFAULT nextval('pool_schema.migrations_id_seq'::regclass);


--
-- TOC entry 5037 (class 2604 OID 26205)
-- Name: partner_groups group_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.partner_groups ALTER COLUMN group_id SET DEFAULT nextval('pool_schema.partner_groups_group_id_seq'::regclass);


--
-- TOC entry 5038 (class 2604 OID 26206)
-- Name: payments payment_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.payments ALTER COLUMN payment_id SET DEFAULT nextval('pool_schema.payments_payment_id_seq'::regclass);


--
-- TOC entry 5041 (class 2604 OID 26207)
-- Name: permissions permission_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.permissions ALTER COLUMN permission_id SET DEFAULT nextval('pool_schema.permissions_permission_id_seq'::regclass);


--
-- TOC entry 5042 (class 2604 OID 26208)
-- Name: plans plan_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.plans ALTER COLUMN plan_id SET DEFAULT nextval('pool_schema.plans_plan_id_seq'::regclass);


--
-- TOC entry 5044 (class 2604 OID 26209)
-- Name: reservations reservation_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.reservations ALTER COLUMN reservation_id SET DEFAULT nextval('pool_schema.reservations_reservation_id_seq'::regclass);


--
-- TOC entry 5047 (class 2604 OID 26210)
-- Name: roles role_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.roles ALTER COLUMN role_id SET DEFAULT nextval('pool_schema.roles_role_id_seq'::regclass);


--
-- TOC entry 5048 (class 2604 OID 26211)
-- Name: staff staff_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.staff ALTER COLUMN staff_id SET DEFAULT nextval('pool_schema.staff_staff_id_seq'::regclass);


--
-- TOC entry 5060 (class 2604 OID 26448)
-- Name: subscription_slots subscription_slot_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscription_slots ALTER COLUMN subscription_slot_id SET DEFAULT nextval('pool_schema.subscription_slots_subscription_slot_id_seq'::regclass);


--
-- TOC entry 5051 (class 2604 OID 26212)
-- Name: subscriptions subscription_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscriptions ALTER COLUMN subscription_id SET DEFAULT nextval('pool_schema.subscriptions_subscription_id_seq'::regclass);


--
-- TOC entry 5055 (class 2604 OID 26213)
-- Name: time_slots slot_id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.time_slots ALTER COLUMN slot_id SET DEFAULT nextval('pool_schema.time_slots_slot_id_seq'::regclass);


--
-- TOC entry 5059 (class 2604 OID 26214)
-- Name: users id; Type: DEFAULT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.users ALTER COLUMN id SET DEFAULT nextval('pool_schema.users_id_seq'::regclass);


--
-- TOC entry 5074 (class 2606 OID 26216)
-- Name: access_badges access_badges_badge_uid_key; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.access_badges
    ADD CONSTRAINT access_badges_badge_uid_key UNIQUE (badge_uid);


--
-- TOC entry 5076 (class 2606 OID 26218)
-- Name: access_badges access_badges_member_id_key; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.access_badges
    ADD CONSTRAINT access_badges_member_id_key UNIQUE (member_id);


--
-- TOC entry 5078 (class 2606 OID 26220)
-- Name: access_badges access_badges_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.access_badges
    ADD CONSTRAINT access_badges_pkey PRIMARY KEY (badge_id);


--
-- TOC entry 5081 (class 2606 OID 26222)
-- Name: access_logs access_logs_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.access_logs
    ADD CONSTRAINT access_logs_pkey PRIMARY KEY (log_id);


--
-- TOC entry 5086 (class 2606 OID 26224)
-- Name: activities activities_name_key; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.activities
    ADD CONSTRAINT activities_name_key UNIQUE (name);


--
-- TOC entry 5088 (class 2606 OID 26226)
-- Name: activities activities_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.activities
    ADD CONSTRAINT activities_pkey PRIMARY KEY (activity_id);


--
-- TOC entry 5090 (class 2606 OID 26228)
-- Name: activity_plan_prices activity_plan_prices_activity_id_plan_id_key; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.activity_plan_prices
    ADD CONSTRAINT activity_plan_prices_activity_id_plan_id_key UNIQUE (activity_id, plan_id);


--
-- TOC entry 5092 (class 2606 OID 26230)
-- Name: activity_plan_prices activity_plan_prices_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.activity_plan_prices
    ADD CONSTRAINT activity_plan_prices_pkey PRIMARY KEY (id);


--
-- TOC entry 5096 (class 2606 OID 26232)
-- Name: audit_log audit_log_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.audit_log
    ADD CONSTRAINT audit_log_pkey PRIMARY KEY (log_id);


--
-- TOC entry 5098 (class 2606 OID 26234)
-- Name: audit_settings audit_settings_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.audit_settings
    ADD CONSTRAINT audit_settings_pkey PRIMARY KEY (id);


--
-- TOC entry 5102 (class 2606 OID 26236)
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- TOC entry 5100 (class 2606 OID 26238)
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- TOC entry 5104 (class 2606 OID 26240)
-- Name: facilities facilities_name_key; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.facilities
    ADD CONSTRAINT facilities_name_key UNIQUE (name);


--
-- TOC entry 5106 (class 2606 OID 26242)
-- Name: facilities facilities_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.facilities
    ADD CONSTRAINT facilities_pkey PRIMARY KEY (facility_id);


--
-- TOC entry 5108 (class 2606 OID 26244)
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- TOC entry 5110 (class 2606 OID 26246)
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- TOC entry 5112 (class 2606 OID 26248)
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- TOC entry 5114 (class 2606 OID 26250)
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- TOC entry 5118 (class 2606 OID 26252)
-- Name: members members_email_key; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.members
    ADD CONSTRAINT members_email_key UNIQUE (email);


--
-- TOC entry 5120 (class 2606 OID 26254)
-- Name: members members_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.members
    ADD CONSTRAINT members_pkey PRIMARY KEY (member_id);


--
-- TOC entry 5122 (class 2606 OID 26256)
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- TOC entry 5124 (class 2606 OID 26258)
-- Name: partner_groups partner_groups_name_key; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.partner_groups
    ADD CONSTRAINT partner_groups_name_key UNIQUE (name);


--
-- TOC entry 5126 (class 2606 OID 26260)
-- Name: partner_groups partner_groups_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.partner_groups
    ADD CONSTRAINT partner_groups_pkey PRIMARY KEY (group_id);


--
-- TOC entry 5128 (class 2606 OID 26262)
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- TOC entry 5133 (class 2606 OID 26264)
-- Name: payments payments_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.payments
    ADD CONSTRAINT payments_pkey PRIMARY KEY (payment_id);


--
-- TOC entry 5135 (class 2606 OID 26266)
-- Name: permissions permissions_permission_name_key; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.permissions
    ADD CONSTRAINT permissions_permission_name_key UNIQUE (permission_name);


--
-- TOC entry 5137 (class 2606 OID 26268)
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (permission_id);


--
-- TOC entry 5139 (class 2606 OID 26270)
-- Name: plans plans_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.plans
    ADD CONSTRAINT plans_pkey PRIMARY KEY (plan_id);


--
-- TOC entry 5141 (class 2606 OID 26272)
-- Name: reservations reservations_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.reservations
    ADD CONSTRAINT reservations_pkey PRIMARY KEY (reservation_id);


--
-- TOC entry 5143 (class 2606 OID 26274)
-- Name: role_permissions role_permissions_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.role_permissions
    ADD CONSTRAINT role_permissions_pkey PRIMARY KEY (role_id, permission_id);


--
-- TOC entry 5145 (class 2606 OID 26276)
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (role_id);


--
-- TOC entry 5147 (class 2606 OID 26278)
-- Name: roles roles_role_name_key; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.roles
    ADD CONSTRAINT roles_role_name_key UNIQUE (role_name);


--
-- TOC entry 5150 (class 2606 OID 26280)
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- TOC entry 5154 (class 2606 OID 26282)
-- Name: staff staff_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.staff
    ADD CONSTRAINT staff_pkey PRIMARY KEY (staff_id);


--
-- TOC entry 5156 (class 2606 OID 26284)
-- Name: staff staff_username_key; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.staff
    ADD CONSTRAINT staff_username_key UNIQUE (username);


--
-- TOC entry 5158 (class 2606 OID 26286)
-- Name: subscription_allowed_days subscription_allowed_days_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscription_allowed_days
    ADD CONSTRAINT subscription_allowed_days_pkey PRIMARY KEY (subscription_id, weekday_id);


--
-- TOC entry 5177 (class 2606 OID 26455)
-- Name: subscription_slots subscription_slots_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscription_slots
    ADD CONSTRAINT subscription_slots_pkey PRIMARY KEY (subscription_slot_id);


--
-- TOC entry 5163 (class 2606 OID 26288)
-- Name: subscriptions subscriptions_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscriptions
    ADD CONSTRAINT subscriptions_pkey PRIMARY KEY (subscription_id);


--
-- TOC entry 5165 (class 2606 OID 26290)
-- Name: time_slots time_slots_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.time_slots
    ADD CONSTRAINT time_slots_pkey PRIMARY KEY (slot_id);


--
-- TOC entry 5167 (class 2606 OID 26292)
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- TOC entry 5169 (class 2606 OID 26294)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- TOC entry 5171 (class 2606 OID 26296)
-- Name: weekdays weekdays_day_name_key; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.weekdays
    ADD CONSTRAINT weekdays_day_name_key UNIQUE (day_name);


--
-- TOC entry 5173 (class 2606 OID 26298)
-- Name: weekdays weekdays_pkey; Type: CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.weekdays
    ADD CONSTRAINT weekdays_pkey PRIMARY KEY (weekday_id);


--
-- TOC entry 5079 (class 1259 OID 26439)
-- Name: idx_access_badges_uid; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_access_badges_uid ON pool_schema.access_badges USING btree (badge_uid);


--
-- TOC entry 5082 (class 1259 OID 26299)
-- Name: idx_access_logs_member_id; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_access_logs_member_id ON pool_schema.access_logs USING btree (member_id);


--
-- TOC entry 5083 (class 1259 OID 26300)
-- Name: idx_access_logs_member_time; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_access_logs_member_time ON pool_schema.access_logs USING btree (member_id, access_time DESC);


--
-- TOC entry 5084 (class 1259 OID 26440)
-- Name: idx_access_logs_uid; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_access_logs_uid ON pool_schema.access_logs USING btree (badge_uid);


--
-- TOC entry 5093 (class 1259 OID 26301)
-- Name: idx_activity_plan_prices_activity_id; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_activity_plan_prices_activity_id ON pool_schema.activity_plan_prices USING btree (activity_id);


--
-- TOC entry 5094 (class 1259 OID 26302)
-- Name: idx_activity_plan_prices_plan_id; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_activity_plan_prices_plan_id ON pool_schema.activity_plan_prices USING btree (plan_id);


--
-- TOC entry 5116 (class 1259 OID 26441)
-- Name: idx_members_email; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_members_email ON pool_schema.members USING btree (email);


--
-- TOC entry 5129 (class 1259 OID 26303)
-- Name: idx_payments_received_by_staff_id; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_payments_received_by_staff_id ON pool_schema.payments USING btree (received_by_staff_id);


--
-- TOC entry 5130 (class 1259 OID 26443)
-- Name: idx_payments_sub; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_payments_sub ON pool_schema.payments USING btree (subscription_id);


--
-- TOC entry 5131 (class 1259 OID 26304)
-- Name: idx_payments_subscription_id; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_payments_subscription_id ON pool_schema.payments USING btree (subscription_id);


--
-- TOC entry 5152 (class 1259 OID 26305)
-- Name: idx_staff_role_id; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_staff_role_id ON pool_schema.staff USING btree (role_id);


--
-- TOC entry 5174 (class 1259 OID 26467)
-- Name: idx_subscription_slots_slot_id; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_subscription_slots_slot_id ON pool_schema.subscription_slots USING btree (slot_id);


--
-- TOC entry 5175 (class 1259 OID 26466)
-- Name: idx_subscription_slots_subscription_id; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_subscription_slots_subscription_id ON pool_schema.subscription_slots USING btree (subscription_id);


--
-- TOC entry 5159 (class 1259 OID 26442)
-- Name: idx_subscriptions_member; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_subscriptions_member ON pool_schema.subscriptions USING btree (member_id);


--
-- TOC entry 5160 (class 1259 OID 26306)
-- Name: idx_subscriptions_member_status; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_subscriptions_member_status ON pool_schema.subscriptions USING btree (member_id, status) WHERE (status = 'active'::pool_schema.subscription_status_enum);


--
-- TOC entry 5161 (class 1259 OID 26307)
-- Name: idx_subscriptions_plan_id; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX idx_subscriptions_plan_id ON pool_schema.subscriptions USING btree (plan_id);


--
-- TOC entry 5115 (class 1259 OID 26308)
-- Name: jobs_queue_index; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX jobs_queue_index ON pool_schema.jobs USING btree (queue);


--
-- TOC entry 5148 (class 1259 OID 26309)
-- Name: sessions_last_activity_index; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX sessions_last_activity_index ON pool_schema.sessions USING btree (last_activity);


--
-- TOC entry 5151 (class 1259 OID 26310)
-- Name: sessions_user_id_index; Type: INDEX; Schema: pool_schema; Owner: pooladmin
--

CREATE INDEX sessions_user_id_index ON pool_schema.sessions USING btree (user_id);


--
-- TOC entry 5204 (class 2620 OID 26473)
-- Name: access_logs trg_audit_access_Logs; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER "trg_audit_access_Logs" BEFORE INSERT OR DELETE OR UPDATE ON pool_schema.access_logs FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5203 (class 2620 OID 26311)
-- Name: access_badges trg_audit_access_badges; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_access_badges AFTER INSERT OR DELETE OR UPDATE ON pool_schema.access_badges FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5205 (class 2620 OID 26471)
-- Name: activities trg_audit_activities; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_activities BEFORE INSERT OR DELETE OR UPDATE ON pool_schema.activities FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5206 (class 2620 OID 26472)
-- Name: activity_plan_prices trg_audit_activity_plan_price; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_activity_plan_price BEFORE INSERT OR DELETE OR UPDATE ON pool_schema.activity_plan_prices FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5207 (class 2620 OID 26474)
-- Name: facilities trg_audit_facilities; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_facilities BEFORE INSERT OR DELETE OR UPDATE ON pool_schema.facilities FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5208 (class 2620 OID 26312)
-- Name: members trg_audit_members; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_members AFTER INSERT OR DELETE OR UPDATE ON pool_schema.members FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5209 (class 2620 OID 26313)
-- Name: payments trg_audit_payments; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_payments AFTER INSERT OR DELETE OR UPDATE ON pool_schema.payments FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5210 (class 2620 OID 26314)
-- Name: permissions trg_audit_permission; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_permission AFTER INSERT OR DELETE OR UPDATE ON pool_schema.permissions FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5211 (class 2620 OID 26315)
-- Name: plans trg_audit_plan; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_plan AFTER INSERT OR DELETE OR UPDATE ON pool_schema.plans FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5212 (class 2620 OID 26475)
-- Name: reservations trg_audit_reservation; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_reservation BEFORE INSERT OR DELETE OR UPDATE ON pool_schema.reservations FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5214 (class 2620 OID 26316)
-- Name: roles trg_audit_role; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_role AFTER INSERT OR DELETE OR UPDATE ON pool_schema.roles FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5213 (class 2620 OID 26317)
-- Name: role_permissions trg_audit_role_permission; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_role_permission AFTER INSERT OR DELETE OR UPDATE ON pool_schema.role_permissions FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5215 (class 2620 OID 26318)
-- Name: staff trg_audit_staff; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_staff AFTER INSERT OR DELETE OR UPDATE ON pool_schema.staff FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5216 (class 2620 OID 26319)
-- Name: subscription_allowed_days trg_audit_subscription_allowed_days; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_subscription_allowed_days AFTER INSERT OR DELETE OR UPDATE ON pool_schema.subscription_allowed_days FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5219 (class 2620 OID 26476)
-- Name: subscription_slots trg_audit_subscription_slots; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_subscription_slots BEFORE INSERT OR DELETE OR UPDATE ON pool_schema.subscription_slots FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5217 (class 2620 OID 26320)
-- Name: subscriptions trg_audit_subscriptions; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_subscriptions AFTER INSERT OR DELETE OR UPDATE ON pool_schema.subscriptions FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5218 (class 2620 OID 26477)
-- Name: time_slots trg_audit_time_slots; Type: TRIGGER; Schema: pool_schema; Owner: pooladmin
--

CREATE TRIGGER trg_audit_time_slots BEFORE INSERT OR DELETE OR UPDATE ON pool_schema.time_slots FOR EACH ROW EXECUTE FUNCTION pool_schema.fn_audit_log_changes();


--
-- TOC entry 5178 (class 2606 OID 26321)
-- Name: access_badges access_badges_member_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.access_badges
    ADD CONSTRAINT access_badges_member_id_fkey FOREIGN KEY (member_id) REFERENCES pool_schema.members(member_id) ON DELETE CASCADE;


--
-- TOC entry 5179 (class 2606 OID 26326)
-- Name: access_logs access_logs_member_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.access_logs
    ADD CONSTRAINT access_logs_member_id_fkey FOREIGN KEY (member_id) REFERENCES pool_schema.members(member_id);


--
-- TOC entry 5180 (class 2606 OID 26331)
-- Name: activity_plan_prices activity_plan_prices_activity_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.activity_plan_prices
    ADD CONSTRAINT activity_plan_prices_activity_id_fkey FOREIGN KEY (activity_id) REFERENCES pool_schema.activities(activity_id) ON DELETE CASCADE;


--
-- TOC entry 5181 (class 2606 OID 26336)
-- Name: activity_plan_prices activity_plan_prices_plan_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.activity_plan_prices
    ADD CONSTRAINT activity_plan_prices_plan_id_fkey FOREIGN KEY (plan_id) REFERENCES pool_schema.plans(plan_id) ON DELETE CASCADE;


--
-- TOC entry 5182 (class 2606 OID 26341)
-- Name: audit_log audit_log_changed_by_staff_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.audit_log
    ADD CONSTRAINT audit_log_changed_by_staff_id_fkey FOREIGN KEY (changed_by_staff_id) REFERENCES pool_schema.staff(staff_id);


--
-- TOC entry 5201 (class 2606 OID 26461)
-- Name: subscription_slots fk_slot; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscription_slots
    ADD CONSTRAINT fk_slot FOREIGN KEY (slot_id) REFERENCES pool_schema.time_slots(slot_id) ON DELETE CASCADE;


--
-- TOC entry 5202 (class 2606 OID 26456)
-- Name: subscription_slots fk_subscription; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscription_slots
    ADD CONSTRAINT fk_subscription FOREIGN KEY (subscription_id) REFERENCES pool_schema.subscriptions(subscription_id) ON DELETE CASCADE;


--
-- TOC entry 5183 (class 2606 OID 26346)
-- Name: payments payments_received_by_staff_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.payments
    ADD CONSTRAINT payments_received_by_staff_id_fkey FOREIGN KEY (received_by_staff_id) REFERENCES pool_schema.staff(staff_id);


--
-- TOC entry 5184 (class 2606 OID 26351)
-- Name: payments payments_subscription_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.payments
    ADD CONSTRAINT payments_subscription_id_fkey FOREIGN KEY (subscription_id) REFERENCES pool_schema.subscriptions(subscription_id);


--
-- TOC entry 5185 (class 2606 OID 26356)
-- Name: reservations reservations_member_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.reservations
    ADD CONSTRAINT reservations_member_id_fkey FOREIGN KEY (member_id) REFERENCES pool_schema.members(member_id) ON DELETE SET NULL;


--
-- TOC entry 5186 (class 2606 OID 26361)
-- Name: reservations reservations_partner_group_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.reservations
    ADD CONSTRAINT reservations_partner_group_id_fkey FOREIGN KEY (partner_group_id) REFERENCES pool_schema.partner_groups(group_id) ON DELETE SET NULL;


--
-- TOC entry 5187 (class 2606 OID 26366)
-- Name: reservations reservations_slot_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.reservations
    ADD CONSTRAINT reservations_slot_id_fkey FOREIGN KEY (slot_id) REFERENCES pool_schema.time_slots(slot_id) ON DELETE CASCADE;


--
-- TOC entry 5188 (class 2606 OID 26371)
-- Name: role_permissions role_permissions_permission_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.role_permissions
    ADD CONSTRAINT role_permissions_permission_id_fkey FOREIGN KEY (permission_id) REFERENCES pool_schema.permissions(permission_id) ON DELETE CASCADE;


--
-- TOC entry 5189 (class 2606 OID 26376)
-- Name: role_permissions role_permissions_role_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.role_permissions
    ADD CONSTRAINT role_permissions_role_id_fkey FOREIGN KEY (role_id) REFERENCES pool_schema.roles(role_id) ON DELETE CASCADE;


--
-- TOC entry 5190 (class 2606 OID 26381)
-- Name: staff staff_role_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.staff
    ADD CONSTRAINT staff_role_id_fkey FOREIGN KEY (role_id) REFERENCES pool_schema.roles(role_id);


--
-- TOC entry 5191 (class 2606 OID 26386)
-- Name: subscription_allowed_days subscription_allowed_days_subscription_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscription_allowed_days
    ADD CONSTRAINT subscription_allowed_days_subscription_id_fkey FOREIGN KEY (subscription_id) REFERENCES pool_schema.subscriptions(subscription_id) ON DELETE CASCADE;


--
-- TOC entry 5192 (class 2606 OID 26391)
-- Name: subscription_allowed_days subscription_allowed_days_weekday_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscription_allowed_days
    ADD CONSTRAINT subscription_allowed_days_weekday_id_fkey FOREIGN KEY (weekday_id) REFERENCES pool_schema.weekdays(weekday_id) ON DELETE CASCADE;


--
-- TOC entry 5193 (class 2606 OID 26396)
-- Name: subscriptions subscriptions_activity_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscriptions
    ADD CONSTRAINT subscriptions_activity_id_fkey FOREIGN KEY (activity_id) REFERENCES pool_schema.activities(activity_id);


--
-- TOC entry 5194 (class 2606 OID 26401)
-- Name: subscriptions subscriptions_created_by_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscriptions
    ADD CONSTRAINT subscriptions_created_by_fkey FOREIGN KEY (created_by) REFERENCES pool_schema.staff(staff_id);


--
-- TOC entry 5195 (class 2606 OID 26406)
-- Name: subscriptions subscriptions_deactivated_by_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscriptions
    ADD CONSTRAINT subscriptions_deactivated_by_fkey FOREIGN KEY (deactivated_by) REFERENCES pool_schema.staff(staff_id);


--
-- TOC entry 5196 (class 2606 OID 26411)
-- Name: subscriptions subscriptions_member_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscriptions
    ADD CONSTRAINT subscriptions_member_id_fkey FOREIGN KEY (member_id) REFERENCES pool_schema.members(member_id) ON DELETE CASCADE;


--
-- TOC entry 5197 (class 2606 OID 26416)
-- Name: subscriptions subscriptions_plan_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscriptions
    ADD CONSTRAINT subscriptions_plan_id_fkey FOREIGN KEY (plan_id) REFERENCES pool_schema.plans(plan_id) ON DELETE RESTRICT;


--
-- TOC entry 5198 (class 2606 OID 26421)
-- Name: subscriptions subscriptions_updated_by_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.subscriptions
    ADD CONSTRAINT subscriptions_updated_by_fkey FOREIGN KEY (updated_by) REFERENCES pool_schema.staff(staff_id);


--
-- TOC entry 5199 (class 2606 OID 26426)
-- Name: time_slots time_slots_activity_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.time_slots
    ADD CONSTRAINT time_slots_activity_id_fkey FOREIGN KEY (activity_id) REFERENCES pool_schema.activities(activity_id) ON DELETE SET NULL;


--
-- TOC entry 5200 (class 2606 OID 26431)
-- Name: time_slots time_slots_weekday_id_fkey; Type: FK CONSTRAINT; Schema: pool_schema; Owner: pooladmin
--

ALTER TABLE ONLY pool_schema.time_slots
    ADD CONSTRAINT time_slots_weekday_id_fkey FOREIGN KEY (weekday_id) REFERENCES pool_schema.weekdays(weekday_id);


--
-- TOC entry 5372 (class 0 OID 0)
-- Dependencies: 6
-- Name: SCHEMA pool_schema; Type: ACL; Schema: -; Owner: pooladmin
--

REVOKE ALL ON SCHEMA pool_schema FROM pooladmin;
GRANT ALL ON SCHEMA pool_schema TO pooladmin WITH GRANT OPTION;


-- Completed on 2025-11-28 09:36:27

--
-- PostgreSQL database dump complete
--

\unrestrict K5ZkHu0YaWxxX4jfhnsZTZqLpea42S5tikbcRJQBXt8ghaYWjrdslqTl6UpB6sd


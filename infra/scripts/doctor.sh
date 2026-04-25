#!/bin/sh
set -u

ROOT_DIR="${ROOT_DIR:-$(pwd)}"
API_DIR="${API_DIR:-$ROOT_DIR/apps/api}"
WEB_DIR="${WEB_DIR:-$ROOT_DIR/apps/web}"
PACKAGES_DIR="${PACKAGES_DIR:-$ROOT_DIR/packages}"
INFRA_DIR="${INFRA_DIR:-$ROOT_DIR/infra}"
COMPOSE_FILE="${COMPOSE_FILE:-$INFRA_DIR/docker-compose.yml}"
HOST_UID="${HOST_UID:-unknown}"
HOST_GID="${HOST_GID:-unknown}"
LEVERLY_BIND_IP="${LEVERLY_BIND_IP:-127.0.0.1}"
LEVERLY_API_PORT="${LEVERLY_API_PORT:-8000}"
LEVERLY_WEB_PORT="${LEVERLY_WEB_PORT:-5173}"
LEVERLY_POSTGRES_PORT="${LEVERLY_POSTGRES_PORT:-5432}"
LEVERLY_REDIS_PORT="${LEVERLY_REDIS_PORT:-6379}"
LEVERLY_MAILPIT_SMTP_PORT="${LEVERLY_MAILPIT_SMTP_PORT:-1025}"
LEVERLY_MAILPIT_UI_PORT="${LEVERLY_MAILPIT_UI_PORT:-8025}"
DOCKER_COMPOSE="${DOCKER_COMPOSE:-docker compose}"

WARNINGS=0

section() {
	printf "\n%s\n" "$1"
}

ok() {
	printf "OK: %s\n" "$1"
}

warn() {
	WARNINGS=$((WARNINGS + 1))
	printf "WARN: %s\n" "$1"
}

skip() {
	printf "SKIP: %s\n" "$1"
}

fix() {
	printf "  Fix: %s\n" "$1"
}

check_command() {
	name="$1"
	fix_text="$2"

	if command -v "$name" >/dev/null 2>&1; then
		ok "$name is available"
	else
		warn "$name is not available"
		fix "$fix_text"
	fi
}

check_port() {
	port="$1"
	label="$2"
	host="${3:-127.0.0.1}"

	if command -v nc >/dev/null 2>&1; then
		if nc -z -w 1 "$host" "$port" >/dev/null 2>&1; then
			if port_owned_by_leverly "$host" "$port"; then
				ok "$label port $port is already served by a Leverly container on $host"
			else
				warn "$label port $port is already in use on $host"
				fix "Stop the conflicting service or change the local port before starting Leverly services."
			fi
		else
			ok "$label port $port is free on $host"
		fi
	else
		skip "Port check for $label skipped because nc is not installed"
		fix "Install netcat if you want doctor to detect local port conflicts."
	fi
}

port_owned_by_leverly() {
	host="$1"
	port="$2"

	if ! command -v docker >/dev/null 2>&1; then
		return 1
	fi

	docker ps --filter "name=leverly_" --format "{{.Ports}}" 2>/dev/null | grep -Fq "$host:$port->"
}

bind_ip_ready() {
	if [ "$LEVERLY_BIND_IP" = "127.0.0.1" ] || [ "$LEVERLY_BIND_IP" = "localhost" ]; then
		return 0
	fi

	if command -v ip >/dev/null 2>&1 && ip -o addr show | grep -Fq " $LEVERLY_BIND_IP/"; then
		return 0
	fi

	return 1
}

section "Leverly doctor"

section "System"
if command -v uname >/dev/null 2>&1; then
	ok "OS detected: $(uname -s)"
else
	warn "Could not detect OS with uname"
	fix "Install standard POSIX system tools."
fi

if [ "$HOST_UID" != "unknown" ] && [ "$HOST_GID" != "unknown" ]; then
	ok "UID/GID detected: $HOST_UID/$HOST_GID"
else
	warn "UID/GID could not be detected"
	fix "Set HOST_UID and HOST_GID when running make commands."
fi

section "Required folders"
for dir in "$API_DIR" "$WEB_DIR" "$PACKAGES_DIR" "$INFRA_DIR"; do
	if [ -d "$dir" ]; then
		ok "Directory exists: $dir"
	else
		warn "Directory missing: $dir"
		fix "Run the monorepo scaffold step or recreate the expected folder."
	fi
done

section "Docker"
if command -v docker >/dev/null 2>&1; then
	ok "docker is available"
	if docker compose version >/dev/null 2>&1; then
		ok "docker compose is available"
	else
		warn "docker compose is not available"
		fix "Install Docker Compose v2 or ensure 'docker compose' works."
	fi
else
	warn "docker is not available"
	fix "Install Docker Desktop, Docker Engine, or a compatible Docker runtime."
fi

section "Local ports"
if bind_ip_ready; then
	check_port "$LEVERLY_API_PORT" "API" "$LEVERLY_BIND_IP"
	check_port "$LEVERLY_WEB_PORT" "Web" "$LEVERLY_BIND_IP"
	check_port "$LEVERLY_POSTGRES_PORT" "PostgreSQL" "$LEVERLY_BIND_IP"
	check_port "$LEVERLY_REDIS_PORT" "Redis" "$LEVERLY_BIND_IP"
	check_port "$LEVERLY_MAILPIT_SMTP_PORT" "Mailpit SMTP" "$LEVERLY_BIND_IP"
	check_port "$LEVERLY_MAILPIT_UI_PORT" "Mailpit UI" "$LEVERLY_BIND_IP"
else
	warn "Bind IP $LEVERLY_BIND_IP is not present on a local interface"
	fix "Add the address to loopback or use the default LEVERLY_BIND_IP=127.0.0.1."
fi

section "Environment files"
if [ -f "$API_DIR/composer.json" ]; then
	if [ -f "$API_DIR/.env" ]; then
		ok "API .env exists"
	else
		warn "API .env is missing"
		fix "Copy apps/api/.env.example to apps/api/.env after the API app is scaffolded."
	fi
	if [ -f "$API_DIR/.env.docker.example" ]; then
		ok "API Docker env example exists"
	else
		warn "API Docker env example is missing"
		fix "Add apps/api/.env.docker.example for Compose-based development."
	fi
else
	skip "API env check skipped because apps/api is not scaffolded yet"
	fix "Scaffold the Laravel API before creating apps/api/.env."
fi

if [ -f "$WEB_DIR/package.json" ]; then
	if [ -f "$WEB_DIR/.env.local" ] || [ -f "$WEB_DIR/.env" ]; then
		ok "Web env file exists"
	elif [ -f "$WEB_DIR/.env.example" ]; then
		ok "Web env example exists"
	else
		warn "Web env file is missing"
		fix "Add apps/web/.env.example and copy it to apps/web/.env.local when running the web app outside Docker."
	fi
else
	skip "Web env check skipped because apps/web is not scaffolded yet"
	fix "Scaffold the Vue app before creating apps/web/.env.local."
fi

section "Dependency folders"
if [ -f "$API_DIR/composer.json" ]; then
	if [ -d "$API_DIR/vendor" ]; then
		ok "API vendor directory exists"
	else
		warn "API vendor directory is missing"
		fix "Run make setup after the API app is scaffolded."
	fi
else
	skip "API dependency check skipped because composer.json does not exist yet"
fi

if [ -f "$WEB_DIR/package.json" ]; then
	if [ -d "$WEB_DIR/node_modules" ]; then
		ok "Web node_modules directory exists"
	else
		warn "Web node_modules directory is missing"
		fix "Run make setup after the web app is scaffolded."
	fi
else
	skip "Web dependency check skipped because package.json does not exist yet"
fi

section "Service health"
if [ -f "$COMPOSE_FILE" ]; then
	if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
		if HOST_UID="$HOST_UID" HOST_GID="$HOST_GID" LEVERLY_BIND_IP="$LEVERLY_BIND_IP" $DOCKER_COMPOSE -f "$COMPOSE_FILE" config >/dev/null 2>&1; then
			ok "Compose configuration is valid"
		else
			warn "Compose configuration could not be validated"
			fix "Run make compose-config and review the reported Compose error."
		fi
		$DOCKER_COMPOSE -f "$COMPOSE_FILE" ps
	else
		warn "Compose file exists but Docker Compose is unavailable"
		fix "Install Docker Compose v2, then rerun make doctor."
	fi
else
	skip "Service health skipped because $COMPOSE_FILE does not exist yet"
	fix "Add Docker Compose before expecting service health checks."
fi

section "Host aliases"
case "${LEVERLY_LOCAL_HOSTS:-0}" in
	1|true|TRUE|yes|YES)
		if [ -r /etc/hosts ]; then
			missing_hosts=""
			for host in leverly.local api.leverly.local web.leverly.local mail.leverly.local; do
				if grep -Eq "(^|[[:space:]])$host([[:space:]]|$)" /etc/hosts; then
					ok "$host is present in /etc/hosts"
				else
					missing_hosts="$missing_hosts $host"
				fi
			done
			if [ -n "$missing_hosts" ]; then
				warn "Some local host aliases are missing:$missing_hosts"
				fix "Add: 10.20.0.1 leverly.local api.leverly.local web.leverly.local mail.leverly.local"
			fi
			if [ "$LEVERLY_BIND_IP" = "10.20.0.1" ]; then
				ok "Custom bind IP is enabled: $LEVERLY_BIND_IP"
			else
				skip "Custom bind IP is not enabled; current LEVERLY_BIND_IP is $LEVERLY_BIND_IP"
				fix "Use LEVERLY_BIND_IP=10.20.0.1 when that address exists on the loopback interface."
			fi
		else
			skip "Host alias check skipped because /etc/hosts is not readable"
			fix "Verify manually that local host aliases resolve or disable LEVERLY_LOCAL_HOSTS."
		fi
		;;
	*)
		skip "Host alias checks skipped because LEVERLY_LOCAL_HOSTS is not enabled"
		;;
esac

section "Summary"
if [ "$WARNINGS" -eq 0 ]; then
	ok "No doctor warnings"
else
	warn "$WARNINGS warning(s) found"
	printf "Review the fixes above before relying on the affected workflow.\n"
fi

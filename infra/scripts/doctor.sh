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

	if command -v nc >/dev/null 2>&1; then
		if nc -z 127.0.0.1 "$port" >/dev/null 2>&1; then
			warn "$label port $port is already in use on 127.0.0.1"
			fix "Stop the conflicting service or change the local port before starting Leverly services."
		else
			ok "$label port $port is free on 127.0.0.1"
		fi
	else
		skip "Port check for $label skipped because nc is not installed"
		fix "Install netcat if you want doctor to detect local port conflicts."
	fi
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
check_port 8000 "API"
check_port 5173 "Web"
check_port 5432 "PostgreSQL"
check_port 6379 "Redis"
check_port 1025 "Mailpit SMTP"
check_port 8025 "Mailpit UI"

section "Environment files"
if [ -f "$API_DIR/composer.json" ]; then
	if [ -f "$API_DIR/.env" ]; then
		ok "API .env exists"
	else
		warn "API .env is missing"
		fix "Copy apps/api/.env.example to apps/api/.env after the API app is scaffolded."
	fi
else
	skip "API env check skipped because apps/api is not scaffolded yet"
	fix "Scaffold the Laravel API before creating apps/api/.env."
fi

if [ -f "$WEB_DIR/package.json" ]; then
	if [ -f "$WEB_DIR/.env.local" ] || [ -f "$WEB_DIR/.env" ]; then
		ok "Web env file exists"
	else
		warn "Web env file is missing"
		fix "Create apps/web/.env.local after the web app is scaffolded."
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
		$DOCKER_COMPOSE -f "$COMPOSE_FILE" ps
	else
		warn "Compose file exists but Docker Compose is unavailable"
		fix "Install Docker Compose v2, then rerun make doctor."
	fi
else
	skip "Service health skipped because $COMPOSE_FILE does not exist yet"
	fix "Add Docker Compose before expecting service health checks."
fi

section "Optional HTTPS"
case "${LEVERLY_LOCAL_HTTPS:-0}" in
	1|true|TRUE|yes|YES)
		if [ -r /etc/hosts ]; then
			if grep -Eq '(^|[[:space:]])leverly\.local([[:space:]]|$)' /etc/hosts; then
				ok "leverly.local is present in /etc/hosts"
			else
				warn "leverly.local is not present in /etc/hosts"
				fix "Add leverly.local to your hosts file or disable LEVERLY_LOCAL_HTTPS."
			fi
		else
			skip "HTTPS host check skipped because /etc/hosts is not readable"
			fix "Verify manually that leverly.local resolves or disable LEVERLY_LOCAL_HTTPS."
		fi
		;;
	*)
		skip "Optional HTTPS checks skipped because LEVERLY_LOCAL_HTTPS is not enabled"
		;;
esac

section "Summary"
if [ "$WARNINGS" -eq 0 ]; then
	ok "No doctor warnings"
else
	warn "$WARNINGS warning(s) found"
	printf "Review the fixes above before relying on the affected workflow.\n"
fi

#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RELEASE_REF="${CANDY_CANE_RELEASE_REF:-HEAD}"
OUTPUT_DIR="${1:-${ROOT_DIR}/dist}"
THEME_DIR='Candy-Cane'

if ! git -C "${ROOT_DIR}" rev-parse --verify --quiet "${RELEASE_REF}^{commit}" >/dev/null; then
	printf 'Candy Cane release ref does not resolve to a commit: %s\n' "${RELEASE_REF}" >&2
	exit 1
fi

version="$(git -C "${ROOT_DIR}" show "${RELEASE_REF}:style.css" | sed -n 's/^Version:[[:space:]]*//p' | head -n 1 | tr -d '\r')"
if [[ -z "${version}" || ! "${version}" =~ ^[0-9]+\.[0-9]+\.[0-9]+([.-][A-Za-z0-9]+)*$ ]]; then
	printf 'Invalid or missing Candy Cane version at %s: %s\n' "${RELEASE_REF}" "${version:-<empty>}" >&2
	exit 1
fi

mkdir -p "${OUTPUT_DIR}"
archive="${OUTPUT_DIR}/${THEME_DIR}-${version}.zip"
checksum="${archive}.sha256"
rm -f "${archive}" "${checksum}"

git -C "${ROOT_DIR}" archive \
	--format=zip \
	--prefix="${THEME_DIR}/" \
	--output="${archive}" \
	"${RELEASE_REF}"

unzip -tqq "${archive}"
(
	cd "${OUTPUT_DIR}"
	archive_name="$(basename "${archive}")"
	checksum_name="$(basename "${checksum}")"

	if command -v sha256sum >/dev/null 2>&1; then
		sha256sum "${archive_name}" > "${checksum_name}"
	elif command -v shasum >/dev/null 2>&1; then
		shasum -a 256 "${archive_name}" > "${checksum_name}"
	else
		printf 'A SHA-256 tool is required: install sha256sum or shasum.\n' >&2
		exit 1
	fi
)

printf 'Built Candy Cane %s release archive from %s: %s\n' "${version}" "${RELEASE_REF}" "${archive}"
printf 'SHA-256: '
cut -d ' ' -f 1 "${checksum}"

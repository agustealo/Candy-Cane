#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RELEASE_REF="${CANDY_CANE_RELEASE_REF:-HEAD}"
OUTPUT_DIR="${1:-${ROOT_DIR}/dist}"
THEME_DIR='Candy-Cane'

version="$(sed -n 's/^Version:[[:space:]]*//p' "${ROOT_DIR}/style.css" | head -n 1 | tr -d '\r')"
if [[ -z "${version}" || ! "${version}" =~ ^[0-9]+\.[0-9]+\.[0-9]+([.-][A-Za-z0-9]+)*$ ]]; then
	printf 'Invalid or missing Candy Cane version in style.css: %s\n' "${version:-<empty>}" >&2
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
	sha256sum "$(basename "${archive}")" > "$(basename "${checksum}")"
)

printf 'Built Candy Cane %s release archive: %s\n' "${version}" "${archive}"
printf 'SHA-256: '
cut -d ' ' -f 1 "${checksum}"

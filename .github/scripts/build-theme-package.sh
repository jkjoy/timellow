#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
BUILD_DIR="${ROOT_DIR}/dist"
PACKAGE_ROOT="${BUILD_DIR}/timellow"
ZIP_PATH="${BUILD_DIR}/timellow.zip"

rm -rf "${BUILD_DIR}"
mkdir -p "${PACKAGE_ROOT}"

while IFS= read -r file; do
    case "${file}" in
        .github/*|.DS_Store|*/.DS_Store)
            continue
            ;;
    esac

    mkdir -p "${PACKAGE_ROOT}/$(dirname "${file}")"
    cp "${ROOT_DIR}/${file}" "${PACKAGE_ROOT}/${file}"
done < <(git -C "${ROOT_DIR}" ls-files)

(
    cd "${BUILD_DIR}"
    zip -r -q "${ZIP_PATH}" timellow
)

echo "Created ${ZIP_PATH}"

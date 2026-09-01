#!/usr/bin/env bash
# Simple Lighthouse runner (bash)
# Usage: ./tools/run-lighthouse.sh http://localhost:8000
URL=${1:-http://localhost:8000}
OUTDIR=${2:-lighthouse-reports}

mkdir -p "$OUTDIR"
TIME=$(date +%Y%m%d-%H%M%S)
OUTHTML="$OUTDIR/lighthouse-$TIME.report.html"
OUTJSON="$OUTDIR/lighthouse-$TIME.report.json"

echo "Running Lighthouse for $URL"

npx -y lighthouse "$URL" \
  --output html --output=json \
  --output-path "$OUTHTML" \
  --chrome-flags="--no-sandbox --headless" \
  --emulated-form-factor=desktop \
  --only-categories=performance,accessibility,best-practices,seo

if [ $? -eq 0 ]; then
  echo "Lighthouse finished. Reports saved to $OUTDIR"
else
  echo "Lighthouse failed with exit code $?"
fi


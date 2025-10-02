#!/usr/bin/env bash
set -euo pipefail
REPO_URL="https://github.com/PrajwalTheCoder/Artgallery-Management.git"
BRANCH="main"

if [ ! -d .git ]; then
  echo "[+] Initializing git repo";
  git init
else
  echo "[i] .git already exists"
fi

# Basic config prompts if empty
name=$(git config user.name || true)
email=$(git config user.email || true)
if [ -z "$name" ]; then
  git config user.name "Prajwal"
fi
if [ -z "$email" ]; then
  git config user.email "prajwal@example.com"  # adjust
fi

echo "[+] Adding files"
git add .

if git diff --cached --quiet; then
  echo "[i] Nothing to commit";
else
  git commit -m "Initial commit: art gallery + ecommerce apps with env-based config" || true
fi

git branch -M "$BRANCH"

if git remote get-url origin >/dev/null 2>&1; then
  echo "[i] Remote origin exists, updating URL";
  git remote set-url origin "$REPO_URL"
else
  echo "[+] Adding remote origin"
  git remote add origin "$REPO_URL"
fi

echo "[+] Pushing to $REPO_URL ($BRANCH)"
git push -u origin "$BRANCH"

echo "[✓] Done. Visit: $REPO_URL"
#!/bin/bash
# Starts the PHP backend server
# Then opens the frontend in your browser

echo "Starting SkillSwap backend on http://localhost:8000 ..."
cd "$(dirname "$0")/backend"

# Open frontend in browser after 1 second
(sleep 1 && open "../frontend/index.html") &

php -S localhost:8000 index.php

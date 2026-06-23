#!/bin/bash
# SkillSwap — Local Setup Script (Mac/Linux, no XAMPP needed)
# Run this once after cloning: bash setup.sh

echo "=== SkillSwap Setup ==="

# Check PHP
if ! command -v php &> /dev/null; then
  echo "ERROR: PHP not found."
  echo "Mac: brew install php"
  echo "Ubuntu: sudo apt install php php-mysqli"
  exit 1
fi
echo "✓ PHP: $(php -v | head -1)"

# Check MySQL
if ! command -v mysql &> /dev/null; then
  echo "ERROR: MySQL not found."
  echo "Mac: brew install mysql && brew services start mysql"
  echo "Ubuntu: sudo apt install mysql-server && sudo systemctl start mysql"
  exit 1
fi
echo "✓ MySQL found"

# Create database and import schema
echo ""
echo "Creating database 'skillswap'..."
echo "Enter your MySQL root password when prompted (just press Enter if no password):"
mysql -u root -p < database/schema.sql
if [ $? -eq 0 ]; then
  echo "✓ Schema imported"
else
  echo "ERROR: Could not import schema. Check MySQL credentials in backend/config/db.php"
  exit 1
fi

echo ""
read -p "Import sample data? (y/n): " seed
if [ "$seed" = "y" ]; then
  mysql -u root -p < database/seed.sql
  echo "✓ Seed data imported (test password for all users: password123)"
fi

echo ""
echo "=== Setup complete! ==="
echo ""
echo "Start the backend (in one terminal):"
echo "  cd backend && php -S localhost:8000"
echo ""
echo "Open the frontend (in another terminal or just open the file):"
echo "  open frontend/index.html"
echo ""
echo "Or run both at once:"
echo "  bash run.sh"

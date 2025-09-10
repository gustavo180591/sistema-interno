#!/bin/bash

# Check if MySQL is running
if ! pgrep -x "mysqld" > /dev/null; then
  echo "MySQL server is not running."
  exit 1
fi

# Try to connect to MySQL server
if mysql -u gustavo -p12345678 -h 127.0.0.1 -P 3313 -e "SELECT 1" 2>/dev/null; then
  echo "Successfully connected to MySQL server."
  
  # List databases
  echo "Listing all databases:"
  mysql -u gustavo -p12345678 -h 127.0.0.1 -P 3313 -e "SHOW DATABASES;"
  
  # Check if the database exists
  DB_EXISTS=$(mysql -u gustavo -p12345678 -h 127.0.0.1 -P 3313 -e "SHOW DATABASES LIKE 'sistema-interno'" | grep -v "Database" | wc -l)
  
  if [ "$DB_EXISTS" -eq 1 ]; then
    echo "Database 'sistema-interno' exists."
    
    # List tables in the database
    echo "Listing tables in 'sistema-interno':"
    mysql -u gustavo -p12345678 -h 127.0.0.1 -P 3313 sistema-interno -e "SHOW TABLES;"
  else
    echo "Database 'sistema-interno' does not exist."
  fi
else
  echo "Failed to connect to MySQL server."
  exit 1
fi

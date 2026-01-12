#!/bin/bash
# verify_init.sh - A simple script to check if the init.php endpoint is working.

echo "--- Testing init.php endpoint ---"
curl -i http://localhost:8000/init.php
echo ""
echo "--- Test complete ---"

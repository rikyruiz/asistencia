#!/bin/bash

# Checkpoint System API Testing Script
# Tests all three API endpoints with real requests

echo "=============================================="
echo "  CHECKPOINT API - End-to-End Test Suite"
echo "=============================================="
echo ""

BASE_URL="https://asistencia.alpefresh.app/api/checkpoint.php"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test credentials - UPDATE THESE WITH VALID SESSION
# Note: This requires an active session. Run from logged-in browser or use session cookie.

echo "⚠️  NOTE: This test requires an active user session."
echo "   You can run these commands from a logged-in browser console instead."
echo ""

# Test 1: Check-in
echo "═══════════════════════════════════════════════"
echo "Test 1: CHECK-IN at a location"
echo "═══════════════════════════════════════════════"

cat << 'EOF'
Request:
curl -X POST https://asistencia.alpefresh.app/api/checkpoint.php \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your_session_id_here" \
  -d '{
    "action": "checkin",
    "location_id": 1,
    "lat": 19.432608,
    "lng": -99.133209,
    "accuracy": 15.5
  }'

Expected Response:
{
  "success": true,
  "action": "checkin",
  "message": "Check-in registrado exitosamente",
  "checkpoint_number": 1,
  "time": "09:00 AM",
  "registro_id": 123
}
EOF

echo ""
echo "═══════════════════════════════════════════════"
echo "Test 2: CHECK-OUT from current location"
echo "═══════════════════════════════════════════════"

cat << 'EOF'
Request:
curl -X POST https://asistencia.alpefresh.app/api/checkpoint.php \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your_session_id_here" \
  -d '{
    "action": "checkout",
    "location_id": 1,
    "lat": 19.432615,
    "lng": -99.133198,
    "accuracy": 12.3
  }'

Expected Response:
{
  "success": true,
  "action": "checkout",
  "message": "Check-out registrado exitosamente",
  "checkpoint_number": 1,
  "hours_worked": 2.5,
  "total_daily_hours": 2.5,
  "time": "11:30 AM"
}
EOF

echo ""
echo "═══════════════════════════════════════════════"
echo "Test 3: TRANSFER to another location"
echo "═══════════════════════════════════════════════"

cat << 'EOF'
Request:
curl -X POST https://asistencia.alpefresh.app/api/checkpoint.php \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your_session_id_here" \
  -d '{
    "action": "transfer",
    "location_id": 2,
    "lat": 19.425608,
    "lng": -99.143209,
    "accuracy": 18.2,
    "reason": "Client meeting"
  }'

Expected Response:
{
  "success": true,
  "action": "transfer",
  "message": "Transferencia registrada exitosamente",
  "time": "02:00 PM",
  "registro_id": 124
}
EOF

echo ""
echo "═══════════════════════════════════════════════"
echo "JavaScript Test (Copy to Browser Console)"
echo "═══════════════════════════════════════════════"

cat << 'EOF'
// Test Check-in
fetch('/api/checkpoint.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    action: 'checkin',
    location_id: 1,
    lat: 19.432608,
    lng: -99.133209,
    accuracy: 15.5
  })
})
.then(r => r.json())
.then(data => console.log('Check-in:', data));

// Test Check-out (after check-in)
fetch('/api/checkpoint.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    action: 'checkout',
    location_id: 1,
    lat: 19.432615,
    lng: -99.133198,
    accuracy: 12.3
  })
})
.then(r => r.json())
.then(data => console.log('Check-out:', data));
EOF

echo ""
echo "=============================================="
echo "  Testing Instructions"
echo "=============================================="
echo ""
echo "Option 1: Browser Testing (Recommended)"
echo "  1. Login to https://asistencia.alpefresh.app"
echo "  2. Open browser console (F12)"
echo "  3. Copy and paste the JavaScript code above"
echo "  4. Check the console output"
echo ""
echo "Option 2: Command Line Testing"
echo "  1. Login to the system in browser"
echo "  2. Get your PHPSESSID cookie value"
echo "  3. Replace 'your_session_id_here' in curl commands"
echo "  4. Run the curl commands"
echo ""
echo "Option 3: UI Testing"
echo "  1. Go to: https://asistencia.alpefresh.app/asistencias_checkpoint.php"
echo "  2. Select a location"
echo "  3. Click 'Hacer Check-In'"
echo "  4. Test the full workflow"
echo ""

<<<<<<< HEAD
<?php
include 'header.php';
include 'sidebar.php';
include 'db.php';

// Get the currently logged-in rider
$rider_id = isset($_SESSION['rider_id']) ? $_SESSION['rider_id'] : 1; 
$active_orders = [];
$query = "SELECT id, customer_name, delivery_address, total_price, status 
          FROM orders 
          WHERE status = 'processing' 
          AND rider_id = $rider_id";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $active_orders[] = $row;
    }
}

// 2. Fetch completed orders TODAY specifically for THIS RIDER
$completed_query = "SELECT COUNT(id) as total_completed, SUM(total_price) as total_earnings 
                    FROM orders 
                    WHERE status = 'delivered' 
                    AND rider_id = $rider_id 
                    AND DATE(order_date) = CURDATE()";
$completed_result = mysqli_query($conn, $completed_query);
$completed_data = mysqli_fetch_assoc($completed_result);

$completed_today = $completed_data['total_completed'] ?? 0;
$daily_earnings = $completed_data['total_earnings'] ?? 0;
?>
<style>
    .rider-stats {
        display: flex;
        gap: 1.2rem;
        margin-bottom: 1.8rem;
        flex-wrap: wrap;
    }

    .stat-card {
        background: white;
        border: 2px solid #e8dcc8;
        border-radius: 16px;
        padding: 1rem 1.4rem;
        flex: 1;
        min-width: 160px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.2s ease;
    }

    .stat-card:hover {
        border-color: saddlebrown;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .stat-left h4 {
        font-size: 11px;
        color: #8b6340;
        font-family: sans-serif;
        letter-spacing: 0.8px;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .stat-left .stat-number {
        font-size: 32px;
        font-weight: 800;
        color: saddlebrown;
        font-family: sans-serif;
        line-height: 1;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        background: #fdf0d5;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: saddlebrown;
        font-size: 1.6rem;
    }

    .map-card {
        background: white;
        border: 2px solid saddlebrown;
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 1rem;
    }

    .map-header {
        background: #fef7e8;
        padding: 1rem 1.5rem;
        border-bottom: 2px solid #e8dcc8;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .map-header h2 {
        font-size: 18px;
        font-weight: 500;
        color: saddlebrown;
        font-family: cursive;
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
    }

    .map-note {
        background: #fff0da;
        padding: 5px 14px;
        border-radius: 30px;
        font-size: 11px;
        color: #a1622b;
        font-family: sans-serif;
    }

    .legend {
        padding: 10px 18px;
        background: #fefaf2;
        border-top: 1px solid #ecd9b9;
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
        font-size: 11px;
        font-family: sans-serif;
        color: #5a3e1b;
    }

    .legend span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .custom-popup {
        font-family: sans-serif;
        min-width: 200px;
    }

    .custom-popup strong {
        color: saddlebrown;
        font-size: 14px;
    }

    .popup-status {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
        margin-top: 5px;
    }

    .status-processing-map {
        background: #fff3cd;
        color: #7a4f00;
    }

    .custom-marker {
        background: transparent;
        border: none;
    }

    .rider-pulse {
        animation: pulse 1.5s ease-in-out infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.2); opacity: 0.8; }
        100% { transform: scale(1); opacity: 1; }
    }
    
    .geocode-status {
        background: #f8f4e8;
        padding: 10px 15px;
        margin: 10px 15px;
        border-radius: 8px;
        font-size: 12px;
        font-family: sans-serif;
        color: #5a3e1b;
        display: none;
    }
    
    .geocode-status.show {
        display: block;
    }
    
    .geocode-status .address-list {
        margin-top: 5px;
        max-height: 100px;
        overflow-y: auto;
    }
    
    .geocode-status .address-item {
        padding: 2px 0;
        border-bottom: 1px solid #e8dcc8;
    }
    
    .geocode-status .address-item.failed {
        color: #c0392b;
    }
    
    .geocode-status .address-item.success {
        color: #27ae60;
    }
</style>

<main class="main" id="main">
    <h1 class="dashboard-title">
        <i class="fa-solid fa-map-location-dot"></i> Live Delivery Map
    </h1>
    <hr class="divider">

    <div class="rider-stats">
        <div class="stat-card">
            <div class="stat-left">
                <h4>ACTIVE DELIVERIES</h4>
                <div class="stat-number" id="activeDeliveries"><?= count($active_orders) ?></div>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-truck-fast"></i></div>
        </div>
        <div class="stat-card">
            <div class="stat-left">
                <h4>TODAY'S EARNINGS</h4>
                <div class="stat-number" id="dailyEarnings">₱<?= number_format($daily_earnings, 2) ?></div>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-coins"></i></div>
        </div>
        <div class="stat-card">
            <div class="stat-left">
                <h4>COMPLETED TODAY</h4>
                <div class="stat-number" id="completedToday"><?= $completed_today ?></div>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
        </div>
    </div>

    <div class="map-card">
        <div class="map-header">
            <h2><i class="fa-solid fa-circle" style="font-size: 12px; color: saddlebrown;"></i> Rider Tracking & Delivery Points</h2>
            <div class="map-note"><i class="fa-regular fa-location-dot"></i> Click markers for order details</div>
        </div>
        
        <!-- Geocoding Status -->
        <div class="geocode-status" id="geocodeStatus">
            <strong>📍 Address Resolution:</strong>
            <div class="address-list" id="addressList"></div>
        </div>
        
        <div id="map" style="height: 500px; width: 100%; border-radius: 0 0 16px 16px;"></div>
        <div class="legend">
            <span><i class="fa-solid fa-shop" style="color: #2e7d32;"></i> Pickup point (Bakery)</span>
            <span><i class="fa-solid fa-location-dot" style="color: #c4451b;"></i> Customer dropoff</span>
            <span><i class="fa-solid fa-motorcycle" style="color: #b96f30;"></i> Your current location</span>
            <span><i class="fa-regular fa-clock"></i> Live updates every 30s</span>
        </div>
    </div>
</main>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    // Import Dynamic DB Data from orders table
    let dbOrders = <?php echo json_encode($active_orders); ?>;

    let riderPosition = {
        lat: 14.6002,
        lng: 120.9845
    };
    let map, riderMarker;
    let deliveryMarkers = [];

    // Custom marker icons
    const pickupIcon = L.divIcon({
        className: 'custom-marker',
        html: '<div style="background:#2e7d32; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid white; box-shadow:0 2px 4px rgba(0,0,0,0.3);"><i class="fa-solid fa-shop" style="color:white; font-size:14px;"></i></div>',
        iconSize: [28, 28],
        popupAnchor: [0, -14]
    });
    
    const dropoffIcon = L.divIcon({
        className: 'custom-marker',
        html: '<div style="background:#c4451b; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid white; box-shadow:0 2px 4px rgba(0,0,0,0.3);"><i class="fa-solid fa-location-dot" style="color:white; font-size:14px;"></i></div>',
        iconSize: [28, 28],
        popupAnchor: [0, -14]
    });
    
    const riderIcon = L.divIcon({
        className: 'custom-marker rider-pulse',
        html: '<div style="background:#b96f30; width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:3px solid gold; box-shadow:0 2px 6px rgba(0,0,0,0.3);"><i class="fa-solid fa-motorcycle" style="color:white; font-size:18px;"></i></div>',
        iconSize: [38, 38],
        popupAnchor: [0, -19]
    });

    // ✅ SPECIAL HANDLING FOR PHILIPPINE ADDRESSES
    function cleanPhilippineAddress(address) {
        let cleaned = address.trim();
        
        // Expand common abbreviations
        const abbreviations = {
            'st.': 'Street',
            'st ': 'Street ',
            'ave.': 'Avenue',
            'ave ': 'Avenue ',
            'blvd.': 'Boulevard',
            'blvd ': 'Boulevard ',
            'cor.': 'corner',
            'cor ': 'corner ',
            'brgy.': 'Barangay',
            'brgy ': 'Barangay ',
            'bgy.': 'Barangay',
            'bgy ': 'Barangay ',
        };
        
        for (const [abbr, full] of Object.entries(abbreviations)) {
            cleaned = cleaned.replace(new RegExp(abbr, 'gi'), full);
        }
        
        return cleaned;
    }

    // ✅ HANDLE COMPOUNDS, SUBDIVISIONS, AND INFORMAL AREAS
    function handleSpecialAreas(address) {
        let searchAddress = address;
        
        // Known compounds and subdivisions in Metro Manila that need special handling
        const specialAreas = {
            'CAA compound': {
                city: 'Pasay City',
                area: 'CAA Compound, Barangay 183',
                coords: { lat: 14.5310, lng: 121.0010 } // Approximate CAA Compound center
            },
            'CAA': {
                city: 'Pasay City',
                area: 'CAA Compound',
                coords: { lat: 14.5310, lng: 121.0010 }
            }
        };
        
        // Check if address contains any known special areas
        for (const [areaName, areaInfo] of Object.entries(specialAreas)) {
            if (address.toLowerCase().includes(areaName.toLowerCase())) {
                // Return the approximate coordinates for known areas
                return {
                    isSpecial: true,
                    coords: areaInfo.coords,
                    enhancedAddress: address.replace(
                        new RegExp(areaName, 'gi'), 
                        areaInfo.area
                    )
                };
            }
        }
        
        return { isSpecial: false, enhancedAddress: address };
    }

    // ✅ FORMAT PHILIPPINE ADDRESS FOR GEOCODING
    function formatPhilippineAddress(address) {
        let cleanAddress = cleanPhilippineAddress(address);
        
        // Remove common prefixes that confuse geocoding
        cleanAddress = cleanAddress.replace(/^(Blk|Block|Lot|Phase|Unit|Room|Apt|Apartment|Suite)\s*[#]?\s*[\w-]+\s*,?\s*/gi, '');
        
        // Handle "corner" streets - format as intersection
        if (cleanAddress.toLowerCase().includes('corner')) {
            cleanAddress = cleanAddress.replace(/\s+corner\s+/gi, ' & ');
        }
        
        // Check if it already contains country info
        if (!/\b(philippines|pilipinas|ph)\b/i.test(cleanAddress)) {
            // Check if it's a Metro Manila address
            const metroManilaCities = [
                'manila', 'quezon city', 'makati', 'pasig', 'taguig', 
                'mandaluyong', 'san juan', 'pasay', 'caloocan', 'malabon',
                'navotas', 'valenzuela', 'marikina', 'parañaque', 'las piñas',
                'muntinlupa', 'pateros'
            ];
            
            const isMetroManila = metroManilaCities.some(city => 
                cleanAddress.toLowerCase().includes(city)
            );
            
            if (isMetroManila) {
                cleanAddress += ', Metro Manila, Philippines';
            } else {
                cleanAddress += ', Philippines';
            }
        }
        
        return cleanAddress;
    }

    // ✅ MULTIPLE GEOCODING STRATEGIES
    async function geocodePhilippineAddress(address) {
        // First, check if it's a known special area
        const specialArea = handleSpecialAreas(address);
        
        if (specialArea.isSpecial) {
            console.log('Using known coordinates for special area:', address);
            
            // Try to get more precise location by searching the enhanced address
            const enhancedCoords = await tryGeocode(formatPhilippineAddress(specialArea.enhancedAddress));
            if (enhancedCoords) {
                return enhancedCoords;
            }
            
            // Fallback to known coordinates
            return specialArea.coords;
        }
        
        const strategies = [
            // Strategy 1: Full formatted address
            async () => {
                const formatted = formatPhilippineAddress(address);
                return await tryGeocode(formatted);
            },
            
            // Strategy 2: Street intersection format (for "corner" addresses)
            async () => {
                if (address.toLowerCase().includes('corner')) {
                    // Convert "Street A corner Street B" to "intersection of Street A and Street B"
                    const parts = address.split(/corner|cor\./gi);
                    if (parts.length === 2) {
                        const intersection = `intersection of ${parts[0].trim()} and ${parts[1].trim()}`;
                        const formatted = formatPhilippineAddress(intersection);
                        return await tryGeocode(formatted);
                    }
                }
                return null;
            },
            
            // Strategy 3: Remove compound/subdivision name, keep streets
            async () => {
                // Remove known compound names but keep street names
                const simplified = address
                    .replace(/CAA\s*compound|BF\s*Homes|Forbes\s*Park|Dasmarinas\s*Village/gi, '')
                    .replace(/(?:Blk|Block|Lot|Phase|Unit|Room|Apt|Apartment|Suite)\s*[#]?\s*[\w-]+\s*,?\s*/gi, '')
                    .replace(/(?:Purok|Sitio|Barangay|Brgy)\s*[#]?\s*[\w-]+\s*,?\s*/gi, '')
                    .replace(/\s+/g, ' ')
                    .trim();
                
                if (simplified !== address && simplified.length > 10) {
                    const formatted = formatPhilippineAddress(simplified);
                    return await tryGeocode(formatted);
                }
                return null;
            },
            
            // Strategy 4: Just city and nearest landmark
            async () => {
                const cityMatch = address.match(/(?:pasay|manila|makati|quezon|pasig|taguig|mandaluyong|san juan|caloocan|malabon|navotas|valenzuela|marikina|parañaque|las piñas|muntinlupa|pateros)\s*city/gi);
                if (cityMatch) {
                    const cityName = cityMatch[0];
                    // Get the street name only
                    const streetPart = address.split(',')[0].replace(/corner/gi, '&').trim();
                    const cityLevel = `${streetPart}, ${cityName}, Metro Manila, Philippines`;
                    return await tryGeocode(cityLevel);
                }
                return null;
            },
            
            // Strategy 5: Google-style search with "near" for landmarks
            async () => {
                if (address.length > 30) {
                    const mainPart = address.split(',')[0].trim();
                    const searchQuery = `${mainPart} near Metro Manila Philippines`;
                    return await tryGeocode(searchQuery);
                }
                return null;
            }
        ];
        
        // Try each strategy
        for (const strategy of strategies) {
            try {
                const coords = await strategy();
                if (coords) {
                    console.log('✅ Successfully geocoded:', address);
                    return coords;
                }
            } catch (error) {
                console.warn('Strategy failed:', error);
            }
        }
        
        console.warn('❌ All strategies failed for:', address);
        return null;
    }

    async function tryGeocode(address) {
        try {
            // Use Nominatim with more specific parameters
            const params = new URLSearchParams({
                format: 'json',
                limit: 3,
                q: address,
                countrycodes: 'ph',
                addressdetails: 1,
                'accept-language': 'en'
            });
            
            const url = `https://nominatim.openstreetmap.org/search?${params.toString()}`;
            
            console.log('Geocoding attempt:', url);
            
            const response = await fetch(url, {
                headers: {
                    'User-Agent': 'LaSeanaleBakeryDeliveryApp/1.0 (Philippines)'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data && data.length > 0) {
                // Filter for results that are actually in the Philippines
                const phResults = data.filter(result => 
                    result.address && 
                    (result.address.country === 'Philippines' || 
                     result.address.country_code === 'ph')
                );
                
                const resultsToUse = phResults.length > 0 ? phResults : data;
                
                // Get the most relevant result
                const bestResult = resultsToUse.reduce((best, current) => {
                    return (current.importance > best.importance) ? current : best;
                }, resultsToUse[0]);
                
                console.log('✅ Geocoded:', address, '→', bestResult.display_name);
                
                return {
                    lat: parseFloat(bestResult.lat),
                    lng: parseFloat(bestResult.lon),
                    displayName: bestResult.display_name
                };
            }
        } catch (error) {
            console.error("Geocoding attempt failed:", error);
        }
        return null;
    }

    // ✅ Update status display
    function updateGeocodeStatus(successCount, failCount, failedAddresses) {
        const statusDiv = document.getElementById('geocodeStatus');
        const addressList = document.getElementById('addressList');
        
        if (failCount > 0 || successCount > 0) {
            statusDiv.classList.add('show');
            let html = '';
            
            // Show failed addresses with suggestions
            failedAddresses.forEach(addr => {
                html += `<div class="address-item failed">
                    ❌ ${addr}
                    <br><small style="color:#888;">Try simplifying the address to street name and city only</small>
                </div>`;
            });
            
            // Show summary
            if (successCount > 0) {
                html += `<div class="address-item success">✅ Successfully located ${successCount} address(es)</div>`;
            }
            
            if (failCount > 0) {
                html += `<div class="address-item" style="color:#e67e22;">
                    💡 Tip: For better results, use format: "Street Name, Barangay, City"
                </div>`;
            }
            
            addressList.innerHTML = html || '<div>No addresses to display</div>';
        }
    }

    async function loadDeliveriesOnMap() {
        // Clear old markers
        deliveryMarkers.forEach(marker => {
            if (marker && map) map.removeLayer(marker);
        });
        deliveryMarkers = [];

        // Add pickup point (Bakery)
        const pickupMarker = L.marker([14.5995, 120.9842], {
                icon: pickupIcon
            })
            .addTo(map)
            .bindPopup(`<div class="custom-popup"><strong>🏪 La Seanale Bakery</strong><br>📍 Main Pickup Point</div>`);
        deliveryMarkers.push(pickupMarker);

        let successCount = 0;
        let failCount = 0;
        let failedAddresses = [];

        // Loop through Database Orders
        for (let order of dbOrders) {
            if (!order.delivery_address || order.delivery_address.trim() === '') {
                console.warn(`Order #${order.id} has no delivery address`);
                failCount++;
                failedAddresses.push(`Order #${order.id}: No address provided`);
                continue;
            }
            
            console.log(`Processing Order #${order.id}: ${order.delivery_address}`);
            
            // Fetch exact coordinates based on the delivery_address column
            const coords = await geocodePhilippineAddress(order.delivery_address);

            // If valid coordinates found, place marker
            if (coords) {
                successCount++;
                
                const popupContent = `
                    <div class="custom-popup">
                        <strong>👤 ${order.customer_name}</strong><br>
                        📍 ${order.delivery_address}<br>
                        💰 ₱${parseFloat(order.total_price).toFixed(2)}<br>
                        <span class="popup-status status-processing-map">${order.status.toUpperCase()}</span>
                        <br><button onclick="markDelivered(${order.id})" style="margin-top:8px; background:saddlebrown; color:white; border:none; border-radius:20px; padding:4px 12px; cursor:pointer; font-size:11px;">✓ Mark Delivered</button>
                    </div>
                `;

                const dropoffMarker = L.marker([coords.lat, coords.lng], {
                        icon: dropoffIcon
                    })
                    .addTo(map)
                    .bindPopup(popupContent);
                deliveryMarkers.push(dropoffMarker);
            } else {
                failCount++;
                failedAddresses.push(`Order #${order.id}: ${order.delivery_address}`);
            }

            // Respect API limits (Nominatim allows ~1 req per second)
            await new Promise(r => setTimeout(r, 1200));
        }
        
        // Update status display
        updateGeocodeStatus(successCount, failCount, failedAddresses);
        
        // Fit map to show all markers
        if (deliveryMarkers.length > 1) {
            const group = L.featureGroup(deliveryMarkers);
            map.fitBounds(group.getBounds().pad(0.1));
        }
    }

    function updateRiderMarker() {
        if (riderMarker && map) map.removeLayer(riderMarker);
        riderMarker = L.marker([riderPosition.lat, riderPosition.lng], {
                icon: riderIcon
            })
            .addTo(map)
            .bindPopup(`<div class="custom-popup"><strong>🛵 You are here</strong></div>`);
    }

    // AJAX Call to update the Database Status
    window.markDelivered = function(orderId) {
        if (!confirm("Are you sure you want to mark this order as delivered?")) return;

        fetch('update_order_map.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `order_id=${orderId}&status=delivered`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`✅ Delivery marked as completed!`);
                    location.reload();
                } else {
                    alert("Failed to update status: " + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Network error. Please try again.");
            });
    };

    function initMap() {
        map = L.map('map').setView([14.5310, 121.0010], 14); // Centered on Pasay City area

        // Google Maps tile layer
        L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            attribution: '© Google Maps'
        }).addTo(map);

        loadDeliveriesOnMap();

        // Get rider's current location
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(position => {
                riderPosition = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                updateRiderMarker();
            }, error => {
                console.warn("Geolocation error:", error.message);
                updateRiderMarker();
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });
        } else {
            updateRiderMarker();
        }
    }

    document.addEventListener('DOMContentLoaded', initMap);
=======
<?php
include 'header.php';
include 'sidebar.php';
include 'db.php';

// Get the currently logged-in rider
$rider_id = isset($_SESSION['rider_id']) ? $_SESSION['rider_id'] : 1; 
$active_orders = [];
$query = "SELECT id, customer_name, delivery_address, total_price, status 
          FROM orders 
          WHERE status = 'processing' 
          AND rider_id = $rider_id";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $active_orders[] = $row;
    }
}

// 2. Fetch completed orders TODAY specifically for THIS RIDER
$completed_query = "SELECT COUNT(id) as total_completed, SUM(total_price) as total_earnings 
                    FROM orders 
                    WHERE status = 'delivered' 
                    AND rider_id = $rider_id 
                    AND DATE(order_date) = CURDATE()";
$completed_result = mysqli_query($conn, $completed_query);
$completed_data = mysqli_fetch_assoc($completed_result);

$completed_today = $completed_data['total_completed'] ?? 0;
$daily_earnings = $completed_data['total_earnings'] ?? 0;
?>
<style>
    .rider-stats {
        display: flex;
        gap: 1.2rem;
        margin-bottom: 1.8rem;
        flex-wrap: wrap;
    }

    .stat-card {
        background: white;
        border: 2px solid #e8dcc8;
        border-radius: 16px;
        padding: 1rem 1.4rem;
        flex: 1;
        min-width: 160px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.2s ease;
    }

    .stat-card:hover {
        border-color: saddlebrown;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .stat-left h4 {
        font-size: 11px;
        color: #8b6340;
        font-family: sans-serif;
        letter-spacing: 0.8px;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .stat-left .stat-number {
        font-size: 32px;
        font-weight: 800;
        color: saddlebrown;
        font-family: sans-serif;
        line-height: 1;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        background: #fdf0d5;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: saddlebrown;
        font-size: 1.6rem;
    }

    .map-card {
        background: white;
        border: 2px solid saddlebrown;
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 1rem;
    }

    .map-header {
        background: #fef7e8;
        padding: 1rem 1.5rem;
        border-bottom: 2px solid #e8dcc8;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .map-header h2 {
        font-size: 18px;
        font-weight: 500;
        color: saddlebrown;
        font-family: cursive;
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
    }

    .map-note {
        background: #fff0da;
        padding: 5px 14px;
        border-radius: 30px;
        font-size: 11px;
        color: #a1622b;
        font-family: sans-serif;
    }

    .legend {
        padding: 10px 18px;
        background: #fefaf2;
        border-top: 1px solid #ecd9b9;
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
        font-size: 11px;
        font-family: sans-serif;
        color: #5a3e1b;
    }

    .legend span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .custom-popup {
        font-family: sans-serif;
        min-width: 200px;
    }

    .custom-popup strong {
        color: saddlebrown;
        font-size: 14px;
    }

    .popup-status {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
        margin-top: 5px;
    }

    .status-processing-map {
        background: #fff3cd;
        color: #7a4f00;
    }

    .custom-marker {
        background: transparent;
        border: none;
    }

    .rider-pulse {
        animation: pulse 1.5s ease-in-out infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.2); opacity: 0.8; }
        100% { transform: scale(1); opacity: 1; }
    }
    
    .geocode-status {
        background: #f8f4e8;
        padding: 10px 15px;
        margin: 10px 15px;
        border-radius: 8px;
        font-size: 12px;
        font-family: sans-serif;
        color: #5a3e1b;
        display: none;
    }
    
    .geocode-status.show {
        display: block;
    }
    
    .geocode-status .address-list {
        margin-top: 5px;
        max-height: 100px;
        overflow-y: auto;
    }
    
    .geocode-status .address-item {
        padding: 2px 0;
        border-bottom: 1px solid #e8dcc8;
    }
    
    .geocode-status .address-item.failed {
        color: #c0392b;
    }
    
    .geocode-status .address-item.success {
        color: #27ae60;
    }
</style>

<main class="main" id="main">
    <h1 class="dashboard-title">
        <i class="fa-solid fa-map-location-dot"></i> Live Delivery Map
    </h1>
    <hr class="divider">

    <div class="rider-stats">
        <div class="stat-card">
            <div class="stat-left">
                <h4>ACTIVE DELIVERIES</h4>
                <div class="stat-number" id="activeDeliveries"><?= count($active_orders) ?></div>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-truck-fast"></i></div>
        </div>
        <div class="stat-card">
            <div class="stat-left">
                <h4>TODAY'S EARNINGS</h4>
                <div class="stat-number" id="dailyEarnings">₱<?= number_format($daily_earnings, 2) ?></div>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-coins"></i></div>
        </div>
        <div class="stat-card">
            <div class="stat-left">
                <h4>COMPLETED TODAY</h4>
                <div class="stat-number" id="completedToday"><?= $completed_today ?></div>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
        </div>
    </div>

    <div class="map-card">
        <div class="map-header">
            <h2><i class="fa-solid fa-circle" style="font-size: 12px; color: saddlebrown;"></i> Rider Tracking & Delivery Points</h2>
            <div class="map-note"><i class="fa-regular fa-location-dot"></i> Click markers for order details</div>
        </div>
        
        <!-- Geocoding Status -->
        <div class="geocode-status" id="geocodeStatus">
            <strong>📍 Address Resolution:</strong>
            <div class="address-list" id="addressList"></div>
        </div>
        
        <div id="map" style="height: 500px; width: 100%; border-radius: 0 0 16px 16px;"></div>
        <div class="legend">
            <span><i class="fa-solid fa-shop" style="color: #2e7d32;"></i> Pickup point (Bakery)</span>
            <span><i class="fa-solid fa-location-dot" style="color: #c4451b;"></i> Customer dropoff</span>
            <span><i class="fa-solid fa-motorcycle" style="color: #b96f30;"></i> Your current location</span>
            <span><i class="fa-regular fa-clock"></i> Live updates every 30s</span>
        </div>
    </div>
</main>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    // Import Dynamic DB Data from orders table
    let dbOrders = <?php echo json_encode($active_orders); ?>;

    let riderPosition = {
        lat: 14.6002,
        lng: 120.9845
    };
    let map, riderMarker;
    let deliveryMarkers = [];

    // Custom marker icons
    const pickupIcon = L.divIcon({
        className: 'custom-marker',
        html: '<div style="background:#2e7d32; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid white; box-shadow:0 2px 4px rgba(0,0,0,0.3);"><i class="fa-solid fa-shop" style="color:white; font-size:14px;"></i></div>',
        iconSize: [28, 28],
        popupAnchor: [0, -14]
    });
    
    const dropoffIcon = L.divIcon({
        className: 'custom-marker',
        html: '<div style="background:#c4451b; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid white; box-shadow:0 2px 4px rgba(0,0,0,0.3);"><i class="fa-solid fa-location-dot" style="color:white; font-size:14px;"></i></div>',
        iconSize: [28, 28],
        popupAnchor: [0, -14]
    });
    
    const riderIcon = L.divIcon({
        className: 'custom-marker rider-pulse',
        html: '<div style="background:#b96f30; width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:3px solid gold; box-shadow:0 2px 6px rgba(0,0,0,0.3);"><i class="fa-solid fa-motorcycle" style="color:white; font-size:18px;"></i></div>',
        iconSize: [38, 38],
        popupAnchor: [0, -19]
    });

    // ✅ SPECIAL HANDLING FOR PHILIPPINE ADDRESSES
    function cleanPhilippineAddress(address) {
        let cleaned = address.trim();
        
        // Expand common abbreviations
        const abbreviations = {
            'st.': 'Street',
            'st ': 'Street ',
            'ave.': 'Avenue',
            'ave ': 'Avenue ',
            'blvd.': 'Boulevard',
            'blvd ': 'Boulevard ',
            'cor.': 'corner',
            'cor ': 'corner ',
            'brgy.': 'Barangay',
            'brgy ': 'Barangay ',
            'bgy.': 'Barangay',
            'bgy ': 'Barangay ',
        };
        
        for (const [abbr, full] of Object.entries(abbreviations)) {
            cleaned = cleaned.replace(new RegExp(abbr, 'gi'), full);
        }
        
        return cleaned;
    }

    // ✅ HANDLE COMPOUNDS, SUBDIVISIONS, AND INFORMAL AREAS
    function handleSpecialAreas(address) {
        let searchAddress = address;
        
        // Known compounds and subdivisions in Metro Manila that need special handling
        const specialAreas = {
            'CAA compound': {
                city: 'Pasay City',
                area: 'CAA Compound, Barangay 183',
                coords: { lat: 14.5310, lng: 121.0010 } // Approximate CAA Compound center
            },
            'CAA': {
                city: 'Pasay City',
                area: 'CAA Compound',
                coords: { lat: 14.5310, lng: 121.0010 }
            }
        };
        
        // Check if address contains any known special areas
        for (const [areaName, areaInfo] of Object.entries(specialAreas)) {
            if (address.toLowerCase().includes(areaName.toLowerCase())) {
                // Return the approximate coordinates for known areas
                return {
                    isSpecial: true,
                    coords: areaInfo.coords,
                    enhancedAddress: address.replace(
                        new RegExp(areaName, 'gi'), 
                        areaInfo.area
                    )
                };
            }
        }
        
        return { isSpecial: false, enhancedAddress: address };
    }

    // ✅ FORMAT PHILIPPINE ADDRESS FOR GEOCODING
    function formatPhilippineAddress(address) {
        let cleanAddress = cleanPhilippineAddress(address);
        
        // Remove common prefixes that confuse geocoding
        cleanAddress = cleanAddress.replace(/^(Blk|Block|Lot|Phase|Unit|Room|Apt|Apartment|Suite)\s*[#]?\s*[\w-]+\s*,?\s*/gi, '');
        
        // Handle "corner" streets - format as intersection
        if (cleanAddress.toLowerCase().includes('corner')) {
            cleanAddress = cleanAddress.replace(/\s+corner\s+/gi, ' & ');
        }
        
        // Check if it already contains country info
        if (!/\b(philippines|pilipinas|ph)\b/i.test(cleanAddress)) {
            // Check if it's a Metro Manila address
            const metroManilaCities = [
                'manila', 'quezon city', 'makati', 'pasig', 'taguig', 
                'mandaluyong', 'san juan', 'pasay', 'caloocan', 'malabon',
                'navotas', 'valenzuela', 'marikina', 'parañaque', 'las piñas',
                'muntinlupa', 'pateros'
            ];
            
            const isMetroManila = metroManilaCities.some(city => 
                cleanAddress.toLowerCase().includes(city)
            );
            
            if (isMetroManila) {
                cleanAddress += ', Metro Manila, Philippines';
            } else {
                cleanAddress += ', Philippines';
            }
        }
        
        return cleanAddress;
    }

    // ✅ MULTIPLE GEOCODING STRATEGIES
    async function geocodePhilippineAddress(address) {
        // First, check if it's a known special area
        const specialArea = handleSpecialAreas(address);
        
        if (specialArea.isSpecial) {
            console.log('Using known coordinates for special area:', address);
            
            // Try to get more precise location by searching the enhanced address
            const enhancedCoords = await tryGeocode(formatPhilippineAddress(specialArea.enhancedAddress));
            if (enhancedCoords) {
                return enhancedCoords;
            }
            
            // Fallback to known coordinates
            return specialArea.coords;
        }
        
        const strategies = [
            // Strategy 1: Full formatted address
            async () => {
                const formatted = formatPhilippineAddress(address);
                return await tryGeocode(formatted);
            },
            
            // Strategy 2: Street intersection format (for "corner" addresses)
            async () => {
                if (address.toLowerCase().includes('corner')) {
                    // Convert "Street A corner Street B" to "intersection of Street A and Street B"
                    const parts = address.split(/corner|cor\./gi);
                    if (parts.length === 2) {
                        const intersection = `intersection of ${parts[0].trim()} and ${parts[1].trim()}`;
                        const formatted = formatPhilippineAddress(intersection);
                        return await tryGeocode(formatted);
                    }
                }
                return null;
            },
            
            // Strategy 3: Remove compound/subdivision name, keep streets
            async () => {
                // Remove known compound names but keep street names
                const simplified = address
                    .replace(/CAA\s*compound|BF\s*Homes|Forbes\s*Park|Dasmarinas\s*Village/gi, '')
                    .replace(/(?:Blk|Block|Lot|Phase|Unit|Room|Apt|Apartment|Suite)\s*[#]?\s*[\w-]+\s*,?\s*/gi, '')
                    .replace(/(?:Purok|Sitio|Barangay|Brgy)\s*[#]?\s*[\w-]+\s*,?\s*/gi, '')
                    .replace(/\s+/g, ' ')
                    .trim();
                
                if (simplified !== address && simplified.length > 10) {
                    const formatted = formatPhilippineAddress(simplified);
                    return await tryGeocode(formatted);
                }
                return null;
            },
            
            // Strategy 4: Just city and nearest landmark
            async () => {
                const cityMatch = address.match(/(?:pasay|manila|makati|quezon|pasig|taguig|mandaluyong|san juan|caloocan|malabon|navotas|valenzuela|marikina|parañaque|las piñas|muntinlupa|pateros)\s*city/gi);
                if (cityMatch) {
                    const cityName = cityMatch[0];
                    // Get the street name only
                    const streetPart = address.split(',')[0].replace(/corner/gi, '&').trim();
                    const cityLevel = `${streetPart}, ${cityName}, Metro Manila, Philippines`;
                    return await tryGeocode(cityLevel);
                }
                return null;
            },
            
            // Strategy 5: Google-style search with "near" for landmarks
            async () => {
                if (address.length > 30) {
                    const mainPart = address.split(',')[0].trim();
                    const searchQuery = `${mainPart} near Metro Manila Philippines`;
                    return await tryGeocode(searchQuery);
                }
                return null;
            }
        ];
        
        // Try each strategy
        for (const strategy of strategies) {
            try {
                const coords = await strategy();
                if (coords) {
                    console.log('✅ Successfully geocoded:', address);
                    return coords;
                }
            } catch (error) {
                console.warn('Strategy failed:', error);
            }
        }
        
        console.warn('❌ All strategies failed for:', address);
        return null;
    }

    async function tryGeocode(address) {
        try {
            // Use Nominatim with more specific parameters
            const params = new URLSearchParams({
                format: 'json',
                limit: 3,
                q: address,
                countrycodes: 'ph',
                addressdetails: 1,
                'accept-language': 'en'
            });
            
            const url = `https://nominatim.openstreetmap.org/search?${params.toString()}`;
            
            console.log('Geocoding attempt:', url);
            
            const response = await fetch(url, {
                headers: {
                    'User-Agent': 'LaSeanaleBakeryDeliveryApp/1.0 (Philippines)'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data && data.length > 0) {
                // Filter for results that are actually in the Philippines
                const phResults = data.filter(result => 
                    result.address && 
                    (result.address.country === 'Philippines' || 
                     result.address.country_code === 'ph')
                );
                
                const resultsToUse = phResults.length > 0 ? phResults : data;
                
                // Get the most relevant result
                const bestResult = resultsToUse.reduce((best, current) => {
                    return (current.importance > best.importance) ? current : best;
                }, resultsToUse[0]);
                
                console.log('✅ Geocoded:', address, '→', bestResult.display_name);
                
                return {
                    lat: parseFloat(bestResult.lat),
                    lng: parseFloat(bestResult.lon),
                    displayName: bestResult.display_name
                };
            }
        } catch (error) {
            console.error("Geocoding attempt failed:", error);
        }
        return null;
    }

    // ✅ Update status display
    function updateGeocodeStatus(successCount, failCount, failedAddresses) {
        const statusDiv = document.getElementById('geocodeStatus');
        const addressList = document.getElementById('addressList');
        
        if (failCount > 0 || successCount > 0) {
            statusDiv.classList.add('show');
            let html = '';
            
            // Show failed addresses with suggestions
            failedAddresses.forEach(addr => {
                html += `<div class="address-item failed">
                    ❌ ${addr}
                    <br><small style="color:#888;">Try simplifying the address to street name and city only</small>
                </div>`;
            });
            
            // Show summary
            if (successCount > 0) {
                html += `<div class="address-item success">✅ Successfully located ${successCount} address(es)</div>`;
            }
            
            if (failCount > 0) {
                html += `<div class="address-item" style="color:#e67e22;">
                    💡 Tip: For better results, use format: "Street Name, Barangay, City"
                </div>`;
            }
            
            addressList.innerHTML = html || '<div>No addresses to display</div>';
        }
    }

    async function loadDeliveriesOnMap() {
        // Clear old markers
        deliveryMarkers.forEach(marker => {
            if (marker && map) map.removeLayer(marker);
        });
        deliveryMarkers = [];

        // Add pickup point (Bakery)
        const pickupMarker = L.marker([14.5995, 120.9842], {
                icon: pickupIcon
            })
            .addTo(map)
            .bindPopup(`<div class="custom-popup"><strong>🏪 La Seanale Bakery</strong><br>📍 Main Pickup Point</div>`);
        deliveryMarkers.push(pickupMarker);

        let successCount = 0;
        let failCount = 0;
        let failedAddresses = [];

        // Loop through Database Orders
        for (let order of dbOrders) {
            if (!order.delivery_address || order.delivery_address.trim() === '') {
                console.warn(`Order #${order.id} has no delivery address`);
                failCount++;
                failedAddresses.push(`Order #${order.id}: No address provided`);
                continue;
            }
            
            console.log(`Processing Order #${order.id}: ${order.delivery_address}`);
            
            // Fetch exact coordinates based on the delivery_address column
            const coords = await geocodePhilippineAddress(order.delivery_address);

            // If valid coordinates found, place marker
            if (coords) {
                successCount++;
                
                const popupContent = `
                    <div class="custom-popup">
                        <strong>👤 ${order.customer_name}</strong><br>
                        📍 ${order.delivery_address}<br>
                        💰 ₱${parseFloat(order.total_price).toFixed(2)}<br>
                        <span class="popup-status status-processing-map">${order.status.toUpperCase()}</span>
                        <br><button onclick="markDelivered(${order.id})" style="margin-top:8px; background:saddlebrown; color:white; border:none; border-radius:20px; padding:4px 12px; cursor:pointer; font-size:11px;">✓ Mark Delivered</button>
                    </div>
                `;

                const dropoffMarker = L.marker([coords.lat, coords.lng], {
                        icon: dropoffIcon
                    })
                    .addTo(map)
                    .bindPopup(popupContent);
                deliveryMarkers.push(dropoffMarker);
            } else {
                failCount++;
                failedAddresses.push(`Order #${order.id}: ${order.delivery_address}`);
            }

            // Respect API limits (Nominatim allows ~1 req per second)
            await new Promise(r => setTimeout(r, 1200));
        }
        
        // Update status display
        updateGeocodeStatus(successCount, failCount, failedAddresses);
        
        // Fit map to show all markers
        if (deliveryMarkers.length > 1) {
            const group = L.featureGroup(deliveryMarkers);
            map.fitBounds(group.getBounds().pad(0.1));
        }
    }

    function updateRiderMarker() {
        if (riderMarker && map) map.removeLayer(riderMarker);
        riderMarker = L.marker([riderPosition.lat, riderPosition.lng], {
                icon: riderIcon
            })
            .addTo(map)
            .bindPopup(`<div class="custom-popup"><strong>🛵 You are here</strong></div>`);
    }

    // AJAX Call to update the Database Status
    window.markDelivered = function(orderId) {
        if (!confirm("Are you sure you want to mark this order as delivered?")) return;

        fetch('update_order_map.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `order_id=${orderId}&status=delivered`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`✅ Delivery marked as completed!`);
                    location.reload();
                } else {
                    alert("Failed to update status: " + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Network error. Please try again.");
            });
    };

    function initMap() {
        map = L.map('map').setView([14.5310, 121.0010], 14); // Centered on Pasay City area

        // Google Maps tile layer
        L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            attribution: '© Google Maps'
        }).addTo(map);

        loadDeliveriesOnMap();

        // Get rider's current location
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(position => {
                riderPosition = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                updateRiderMarker();
            }, error => {
                console.warn("Geolocation error:", error.message);
                updateRiderMarker();
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });
        } else {
            updateRiderMarker();
        }
    }

    document.addEventListener('DOMContentLoaded', initMap);
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
</script>
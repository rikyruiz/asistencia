/**
 * Geolocation Service for Attendance System
 */

class GeolocationService {
    constructor() {
        this.watchId = null;
        this.currentPosition = null;
        this.accuracy = null;
        this.lastUpdate = null;
        this.callbacks = {
            onUpdate: null,
            onError: null,
            onStatusChange: null
        };
        this.options = {
            enableHighAccuracy: true,
            timeout: 30000, // 30 seconds
            maximumAge: 0
        };
    }

    /**
     * Check if geolocation is supported
     */
    isSupported() {
        return 'geolocation' in navigator;
    }

    /**
     * Request geolocation permission
     */
    async requestPermission() {
        if (!this.isSupported()) {
            throw new Error('Geolocalización no es soportada por este navegador');
        }

        try {
            // Check current permission status if available
            if ('permissions' in navigator) {
                const permission = await navigator.permissions.query({ name: 'geolocation' });
                return permission.state;
            }
            return 'prompt';
        } catch (error) {
            console.error('Error checking permission:', error);
            return 'prompt';
        }
    }

    /**
     * Get current position once
     */
    getCurrentPosition() {
        return new Promise((resolve, reject) => {
            if (!this.isSupported()) {
                reject(new Error('Geolocalización no disponible'));
                return;
            }

            this.updateStatus('locating', 'Obteniendo ubicación...');

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.updatePosition(position);
                    this.updateStatus('success', 'Ubicación obtenida');
                    resolve(this.formatPosition(position));
                },
                (error) => {
                    this.handleError(error);
                    reject(error);
                },
                this.options
            );
        });
    }

    /**
     * Start watching position
     */
    startWatching(callbacks = {}) {
        if (!this.isSupported()) {
            throw new Error('Geolocalización no disponible');
        }

        // Set callbacks
        this.callbacks = { ...this.callbacks, ...callbacks };

        // Clear existing watch
        if (this.watchId !== null) {
            this.stopWatching();
        }

        this.updateStatus('watching', 'Monitoreando ubicación...');

        this.watchId = navigator.geolocation.watchPosition(
            (position) => {
                this.updatePosition(position);
                if (this.callbacks.onUpdate) {
                    this.callbacks.onUpdate(this.formatPosition(position));
                }
            },
            (error) => {
                this.handleError(error);
                if (this.callbacks.onError) {
                    this.callbacks.onError(error);
                }
            },
            this.options
        );
    }

    /**
     * Stop watching position
     */
    stopWatching() {
        if (this.watchId !== null) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
            this.updateStatus('stopped', 'Monitoreo detenido');
        }
    }

    /**
     * Update current position
     */
    updatePosition(position) {
        this.currentPosition = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            altitude: position.coords.altitude,
            altitudeAccuracy: position.coords.altitudeAccuracy,
            heading: position.coords.heading,
            speed: position.coords.speed
        };
        this.accuracy = position.coords.accuracy;
        this.lastUpdate = new Date();
    }

    /**
     * Format position for API
     */
    formatPosition(position) {
        return {
            lat: position.coords.latitude,
            lng: position.coords.longitude,
            accuracy: Math.round(position.coords.accuracy),
            timestamp: new Date().toISOString()
        };
    }

    /**
     * Handle geolocation errors
     */
    handleError(error) {
        let message = 'Error desconocido';
        let code = 'UNKNOWN';

        switch (error.code) {
            case error.PERMISSION_DENIED:
                message = 'Permiso de ubicación denegado. Por favor, habilita la ubicación en tu navegador.';
                code = 'PERMISSION_DENIED';
                break;
            case error.POSITION_UNAVAILABLE:
                message = 'Información de ubicación no disponible. Verifica tu GPS.';
                code = 'POSITION_UNAVAILABLE';
                break;
            case error.TIMEOUT:
                message = 'Tiempo de espera agotado al obtener la ubicación.';
                code = 'TIMEOUT';
                break;
        }

        this.updateStatus('error', message, code);
        console.error('Geolocation error:', error);
    }

    /**
     * Update status
     */
    updateStatus(status, message, code = null) {
        if (this.callbacks.onStatusChange) {
            this.callbacks.onStatusChange({ status, message, code });
        }
    }

    /**
     * Calculate distance between two points (Haversine formula)
     */
    static calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371000; // Earth's radius in meters
        const φ1 = lat1 * Math.PI / 180;
        const φ2 = lat2 * Math.PI / 180;
        const Δφ = (lat2 - lat1) * Math.PI / 180;
        const Δλ = (lon2 - lon1) * Math.PI / 180;

        const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                  Math.cos(φ1) * Math.cos(φ2) *
                  Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return R * c; // Distance in meters
    }

    /**
     * Check if position is within geofence
     */
    static isWithinGeofence(userLat, userLon, centerLat, centerLon, radius) {
        const distance = GeolocationService.calculateDistance(userLat, userLon, centerLat, centerLon);
        return distance <= radius;
    }

    /**
     * Get accuracy level description
     */
    static getAccuracyLevel(accuracy) {
        if (accuracy <= 10) return { level: 'excellent', text: 'Excelente', color: 'green' };
        if (accuracy <= 30) return { level: 'good', text: 'Buena', color: 'blue' };
        if (accuracy <= 50) return { level: 'fair', text: 'Aceptable', color: 'yellow' };
        if (accuracy <= 100) return { level: 'poor', text: 'Baja', color: 'orange' };
        return { level: 'very-poor', text: 'Muy Baja', color: 'red' };
    }

    /**
     * Format distance for display
     */
    static formatDistance(meters) {
        if (meters < 1000) {
            return `${Math.round(meters)} m`;
        }
        return `${(meters / 1000).toFixed(1)} km`;
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = GeolocationService;
}
<?php

namespace App\Core;

/**
 * SecurityHeaders
 * 
 * Manages HTTP security headers for the application
 * Implements defense-in-depth security strategy
 */
class SecurityHeaders {
    
    /**
     * Apply default security headers
     * Should be called early in the request lifecycle
     * 
     * @return void
     */
    public static function applyDefault(): void {
        $isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';
        
        // XSS Protection (Legacy header, still useful for older browsers)
        header("X-XSS-Protection: 1; mode=block");
        
        // Prevent MIME-type sniffing
        // Forces browsers to respect declared Content-Type
        header("X-Content-Type-Options: nosniff");
        
        // Clickjacking protection
        // Prevents the page from being embedded in frames/iframes
        header("X-Frame-Options: DENY");
        
        // Referrer Policy
        // Don't send referrer information to protect user privacy
        header("Referrer-Policy: no-referrer");
        
        // Content Security Policy (CSP)
        // Restrictive policy to prevent XSS and other injection attacks
        // default-src 'self' data: - Only allow resources from same origin + data URIs
        // form-action 'self' - Forms can only submit to same origin
        // base-uri 'self' - Restrict base tag to prevent injection attacks
        // Note: Can be extended with specific permissions if external libraries are added
        $csp = implode('; ', [
            "default-src 'self' data:",
            "script-src 'self' 'unsafe-inline'",  // unsafe-inline needed for inline scripts
            "style-src 'self' 'unsafe-inline'",   // unsafe-inline needed for inline styles
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "frame-ancestors 'none'"
        ]);
        header("Content-Security-Policy: {$csp}");
        
        // Strict-Transport-Security (HSTS)
        // Only in production - forces HTTPS for 1 year
        // includeSubDomains - applies to all subdomains
        // preload - eligible for browser HSTS preload list
        if ($isProduction) {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        }
        
        // Permissions Policy (formerly Feature-Policy)
        // Disable unnecessary browser features to reduce attack surface
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
    }
    
    /**
     * Set cache control headers for session-related pages
     * Prevents caching of sensitive data
     * 
     * @return void
     */
    public static function noCache(): void {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        header("Expires: 0");
    }
    
    /**
     * Set cache control headers for public/static content
     * Allows caching for better performance
     * 
     * @param int $maxAge Cache duration in seconds (default: 1 hour)
     * @return void
     */
    public static function publicCache(int $maxAge = 3600): void {
        header("Cache-Control: public, max-age={$maxAge}");
    }
    
    /**
     * Legacy method for backward compatibility
     * @deprecated Use applyDefault() instead
     */
    public static function set(): void {
        self::applyDefault();
    }
}


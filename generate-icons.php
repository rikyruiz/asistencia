<?php
// Generate PWA icons

function createIcon($size) {
    // Create image
    $image = imagecreatetruecolor($size, $size);

    // Colors
    $navy = imagecolorallocate($image, 0, 31, 63);
    $blue = imagecolorallocate($image, 0, 64, 128);
    $white = imagecolorallocate($image, 255, 255, 255);
    $gold = imagecolorallocate($image, 255, 215, 0);

    // Fill background with gradient effect (simplified)
    imagefilledrectangle($image, 0, 0, $size, $size, $navy);

    // Draw white circle in center
    $centerX = $size / 2;
    $centerY = $size / 2;
    $radius = $size / 3;
    imagefilledellipse($image, $centerX, $centerY, $radius * 2, $radius * 2, $white);

    // Draw clock outline
    $clockRadius = $size / 4;
    imagearc($image, $centerX, $centerY, $clockRadius * 2, $clockRadius * 2, 0, 360, $navy);

    // Draw clock hands (simplified)
    imageline($image, $centerX, $centerY, $centerX, $centerY - $clockRadius * 0.6, $navy);
    imageline($image, $centerX, $centerY, $centerX + $clockRadius * 0.4, $centerY, $navy);

    // Add "A" letter for Asistencia
    $fontSize = $size / 8;
    $text = "A";
    $bbox = imagettfbbox($fontSize, 0, '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf', $text);
    $textX = $centerX - ($bbox[2] - $bbox[0]) / 2;
    $textY = $centerY + $radius * 1.5;

    // Save as PNG
    $filename = __DIR__ . '/assets/images/icon-' . $size . '.png';
    imagepng($image, $filename);
    imagedestroy($image);

    echo "Created: $filename\n";
}

// Generate icons
createIcon(192);
createIcon(512);

echo "Icons generated successfully!\n";
?>
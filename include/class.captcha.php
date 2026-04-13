<?php
class Captcha {

    public string $hash;
    public int $font;

    private int $width  = 220;
    private int $height = 70;

    private string $charset = 'ABCDEFGHJKMNPRSTUVWXYZ23456789';

    public function __construct(int $len = 6, int $font = 28, string $bg = '') {

        $this->font = $font > 5 ? $font : 28;
        $this->hash = '';
        $max = strlen($this->charset) - 1;
        for ($i = 0; $i < $len; $i++) {
            $this->hash .= $this->charset[random_int(0, $max)];
        }
    }

    public function getImage(): void {

        if (!extension_loaded('gd') || !function_exists('gd_info'))
            return;

        $_SESSION['captcha'] = '';

        $img = imagecreatetruecolor($this->width, $this->height);
        imagealphablending($img, true);

        $this->drawGradient($img);
        $this->drawBackgroundNoise($img);

        $fontFile = $this->findFont();
        if ($fontFile !== null) {
            $this->drawCharactersTTF($img, $fontFile);
        } else {
            $this->drawCharactersBuiltin($img);
        }

        $this->drawForegroundNoise($img);
        $img = $this->applyWave($img);

        header('Content-Type: image/png');
        imagepng($img);
        imagedestroy($img);

        $_SESSION['captcha'] = $this->hash;
    }

    private function drawGradient(\GdImage $img): void {

        $r1 = random_int(225, 245); $g1 = random_int(230, 248); $b1 = random_int(235, 250);
        $r2 = random_int(210, 235); $g2 = random_int(215, 238); $b2 = random_int(225, 245);

        for ($y = 0; $y < $this->height; $y++) {
            $t = $y / max($this->height - 1, 1);
            $r = (int)($r1 + ($r2 - $r1) * $t);
            $g = (int)($g1 + ($g2 - $g1) * $t);
            $b = (int)($b1 + ($b2 - $b1) * $t);
            $c = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, $this->width, $y, $c);
        }
    }

    private function drawBackgroundNoise(\GdImage $img): void {

        for ($i = 0; $i < 200; $i++) {
            $c = imagecolorallocate($img,
                random_int(170, 210), random_int(170, 210), random_int(180, 215));
            imagesetpixel($img, random_int(0, $this->width - 1),
                                random_int(0, $this->height - 1), $c);
        }

        for ($i = 0; $i < 4; $i++) {
            $c = imagecolorallocate($img,
                random_int(185, 215), random_int(185, 215), random_int(195, 225));
            imageline($img,
                random_int(0, 30), random_int(0, $this->height),
                random_int($this->width - 30, $this->width), random_int(0, $this->height),
                $c);
        }
    }

    private function drawCharactersTTF(\GdImage $img, string $fontFile): void {

        $len      = strlen($this->hash);
        $fontSize = $this->font;
        $padding  = 18;
        $step     = (int)(($this->width - $padding * 2) / $len);

        for ($i = 0; $i < $len; $i++) {
            $char  = $this->hash[$i];
            $angle = random_int(-25, 25);

            $r = random_int(30, 110);
            $g = random_int(20, 90);
            $b = random_int(50, 140);
            $color = imagecolorallocate($img, $r, $g, $b);

            $x = $padding + $i * $step + random_int(-2, 2);
            $y = (int)($this->height / 2 + $fontSize / 3) + random_int(-6, 6);

            imagettftext($img, $fontSize, $angle, $x, $y, $color, $fontFile, $char);
        }
    }

    private function drawCharactersBuiltin(\GdImage $img): void {

        $gdFont   = 5;
        $scale    = 3;
        $fw       = imagefontwidth($gdFont);
        $fh       = imagefontheight($gdFont);
        $len      = strlen($this->hash);
        $padding  = 14;
        $step     = (int)(($this->width - $padding * 2) / $len);
        $charW    = $fw * $scale;
        $charH    = $fh * $scale;

        $tR = 255; $tG = 0; $tB = 255;

        for ($i = 0; $i < $len; $i++) {
            $char  = $this->hash[$i];
            $angle = random_int(-25, 25);

            $r = random_int(30, 110);
            $g = random_int(20, 90);
            $b = random_int(50, 140);

            $tiny = imagecreatetruecolor($fw + 2, $fh + 2);
            $tbg  = imagecolorallocate($tiny, $tR, $tG, $tB);
            imagefill($tiny, 0, 0, $tbg);
            $tc = imagecolorallocate($tiny, $r, $g, $b);
            imagestring($tiny, $gdFont, 1, 1, $char, $tc);

            $scaled = imagecreatetruecolor($charW, $charH);
            $sbg    = imagecolorallocate($scaled, $tR, $tG, $tB);
            imagefill($scaled, 0, 0, $sbg);
            imagecopyresampled($scaled, $tiny, 0, 0, 0, 0,
                               $charW, $charH, $fw + 2, $fh + 2);
            imagedestroy($tiny);

            $rot = imagerotate($scaled, $angle,
                               imagecolorallocate($scaled, $tR, $tG, $tB));
            imagedestroy($scaled);

            imagecolortransparent($rot, imagecolorallocate($rot, $tR, $tG, $tB));

            $rw = imagesx($rot);
            $rh = imagesy($rot);
            $dx = $padding + $i * $step - (int)(($rw - $step) / 2);
            $dy = (int)(($this->height - $rh) / 2) + random_int(-4, 4);
            imagecopy($img, $rot, $dx, $dy, 0, 0, $rw, $rh);
            imagedestroy($rot);
        }
    }

    private function drawForegroundNoise(\GdImage $img): void {

        for ($i = 0; $i < 5; $i++) {
            $c = imagecolorallocate($img,
                random_int(80, 160), random_int(80, 150), random_int(100, 180));
            imagesetthickness($img, random_int(1, 2));
            imageline($img,
                random_int(0, 30), random_int(0, $this->height),
                random_int($this->width - 30, $this->width), random_int(0, $this->height),
                $c);
        }
        imagesetthickness($img, 1);

        $amp   = random_int(4, 10);
        $freq  = random_int(25, 50);
        $phase = random_int(0, 100);
        $yc    = (int)($this->height / 2);
        $c     = imagecolorallocate($img,
            random_int(60, 130), random_int(60, 120), random_int(80, 150));

        for ($x = 0; $x < $this->width; $x++) {
            $y = (int)($yc + $amp * sin(($x + $phase) * 2 * M_PI / $freq));
            imagesetpixel($img, $x, $y, $c);
            imagesetpixel($img, $x, $y + 1, $c);
        }
    }

    private function applyWave(\GdImage $src): \GdImage {

        $amp   = random_int(3, 6);
        $freq  = random_int(40, 80);
        $phase = random_int(0, 100);

        $dst = imagecreatetruecolor($this->width, $this->height);
        $bg  = imagecolorallocate($dst, 240, 242, 248);
        imagefill($dst, 0, 0, $bg);

        for ($x = 0; $x < $this->width; $x++) {
            $shift = (int)($amp * sin(($x + $phase) * 2 * M_PI / $freq));
            imagecopy($dst, $src, $x, $shift, $x, 0, 1, $this->height);
        }

        imagedestroy($src);
        return $dst;
    }

    private function findFont(): ?string {

        if (!function_exists('imagettftext'))
            return null;

        $candidates = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
            '/usr/share/fonts/truetype/ubuntu/Ubuntu-Bold.ttf',
            'C:/Windows/Fonts/arial.ttf',
            'C:/Windows/Fonts/arialbd.ttf',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path))
                return $path;
        }

        return null;
    }
}
?>

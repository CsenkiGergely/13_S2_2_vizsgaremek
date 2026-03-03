/**
 * decode-qr.js
 *
 * JPEG képből QR kódot dekódol
 * Használat (Laravel PHP hívja):
 *   node decode-qr.js <jpeg_fájl_elérési_út>
 *
 * Kimenet (JSON):
 *   { "success": true,  "data": "<qr_tartalom>" }
 *   { "success": false, "error": "<hibaüzenet>" }
 */

import Jimp from 'jimp';
import jsQR from 'jsqr';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';
const __dirname = path.dirname(fileURLToPath(import.meta.url));

function out(obj) {
    process.stdout.write(JSON.stringify(obj) + '\n');
}

/**
 * jsQR-t futtat egy Jimp képen, mindkét invertálási irányban egyszerre.
 */
function tryDecode(img) {
    const { width, height, data } = img.bitmap;
    return jsQR(new Uint8ClampedArray(data), width, height, {
        inversionAttempts: 'attemptBoth',
    });
}

/**
 * jsQR 400–800px
 */
function normalizeSize(img, targetMax = 800) {
    const { width, height } = img.bitmap;
    const maxDim = Math.max(width, height);
    if (maxDim <= targetMax) return img.clone();
    const scale = targetMax / maxDim;
    return img.clone().resize(Math.round(width * scale), Math.round(height * scale));
}

/**
 * Képet felnagyít egy adott célméretre (hosszabb oldal = targetPx).
 * RESIZE_NEAREST_NEIGHBOR: éles pixel szélek, nem elmosódott – QR-hoz ideális.
 */
function rescale(img, targetPx) {
    const { width, height } = img.bitmap;
    const maxDim = Math.max(width, height);
    if (maxDim === targetPx) return img.clone();
    const scale = targetPx / maxDim;
    return img.clone().resize(
        Math.round(width * scale),
        Math.round(height * scale),
        Jimp.RESIZE_NEAREST_NEIGHBOR
    );
}

/**
 * Kis képet felnagyít egy célméretre (legalább targetMin px legyen a hosszabb oldala).
 */
function upscaleSmall(img, targetMin = 600) {
    const { width, height } = img.bitmap;
    const maxDim = Math.max(width, height);
    if (maxDim >= targetMin) return img.clone();
    return rescale(img, targetMin);
}

/**
 * Fehér keretet (quiet zone) ad a kép köré.
 * jsQR megköveteli, hogy a QR kód körül fehér terület legyen.
 */
async function addPadding(img, paddingPx = 40) {
    const { width, height } = img.bitmap;
    const canvas = await Jimp.create(width + paddingPx * 2, height + paddingPx * 2, 0xffffffff);
    canvas.blit(img, paddingPx, paddingPx);
    return canvas;
}

async function main() {
    const filePath = process.argv[2];

    if (!filePath) {
        out({ success: false, error: 'Hiányzó argumentum: <jpeg_fájl_elérési_út>' });
        process.exit(1);
    }

    const absPath = path.resolve(filePath);
    if (!fs.existsSync(absPath)) {
        out({ success: false, error: `A fájl nem létezik: ${absPath}` });
        process.exit(1);
    }

    let original;
    try {
        original = await Jimp.read(absPath);
    } catch (e) {
        out({ success: false, error: `Nem sikerült beolvasni a képet: ${e.message}` });
        process.exit(1);
    }

    //Preprocess
    const { width: origW, height: origH } = original.bitmap;
    const origMax = Math.max(origW, origH);

    
    const s600  = rescale(original, Math.max(origMax, 600));
    const s800  = rescale(original, Math.max(origMax, 800));
    const s1200 = rescale(original, Math.max(origMax, 1200));
    const s1600 = rescale(original, Math.max(origMax, 1600));

    const basePadded = await addPadding(s800, 40);

    const strategies = [
        
        () => original.clone(),
        async () => addPadding(original.clone(), 20),

        // 600px
        () => s600,
        () => s600.clone().grayscale().contrast(0.3),
        () => s600.clone().grayscale().threshold({ max: 128 }),
        async () => addPadding(s600, 30),
        async () => addPadding(s600.clone().grayscale().contrast(0.3), 30),

        // 800px
        () => s800,
        () => basePadded,
        () => s800.clone().grayscale().contrast(0.3),
        () => basePadded.clone().grayscale().contrast(0.3),
        () => s800.clone().grayscale().contrast(0.6),
        () => s800.clone().normalize().grayscale().contrast(0.3),
        () => s800.clone().grayscale().threshold({ max: 128 }),
        async () => addPadding(s800.clone().grayscale().threshold({ max: 128 }), 40),

        // 1200px
        () => s1200,
        () => s1200.clone().grayscale().contrast(0.3),
        () => s1200.clone().grayscale().contrast(0.5),
        () => s1200.clone().grayscale().threshold({ max: 128 }),
        async () => addPadding(s1200, 50),
        async () => addPadding(s1200.clone().grayscale().contrast(0.3), 50),
        async () => addPadding(s1200.clone().grayscale().threshold({ max: 128 }), 50),

        // 1600px
        () => s1600,
        () => s1600.clone().grayscale().contrast(0.3),
        () => s1600.clone().grayscale().threshold({ max: 128 }),
        async () => addPadding(s1600.clone().grayscale().threshold({ max: 128 }), 60),
    ];

    for (let i = 0; i < strategies.length; i++) {
        let processed;
        try {
            processed = await Promise.resolve(strategies[i]());
        } catch {
            continue;
        }

        const result = tryDecode(processed);
        if (result) {
            out({ success: true, data: result.data });
            process.exit(0);
        }
    }

    out({ success: false, error: 'Nem található QR kód a képen.' });
    process.exit(1);
}

main().catch((e) => {
    out({ success: false, error: e.message });
    process.exit(1);
});

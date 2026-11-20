// ============================================================
// PHISHGUARD - GMAIL SCANNER
// ============================================================

const PHISHGUARD_API = 'http://localhost:8000/api/email/check';
let scannedLinks = new Set();

// Main scan function
function scanEmailLinks() {
    console.log('🛡️ PhishGuard: Scanning email links...');

    // Scan text links
    const links = document.querySelectorAll('a[href]:not(a img)');
    links.forEach((link) => {
        const url = link.href;
        if (scannedLinks.has(url) ||
            url.startsWith('#') ||
            url.startsWith('mailto:') ||
            url.startsWith('tel:') ||
            url.startsWith('javascript:')) {
            return;
        }
        scannedLinks.add(url);
        scanLink(url, link);
    });

    // Scan clickable images
    const images = document.querySelectorAll('a img');
    images.forEach((img) => {
        const link = img.closest('a');
        if (!link || link.dataset.phishguardScanned) return;
        link.dataset.phishguardScanned = 'true';

        const url = link.href;
        const imgSrc = img.src || '';
        const altText = img.alt || '';

        if (scannedLinks.has(url)) return;
        scannedLinks.add(url);

        scanImageLink(url, link, { imgSrc, altText });
    });
}

// Scan text link
function scanLink(url, linkElement) {
    fetch(PHISHGUARD_API, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            url: url,
            email: getSenderEmail(),
            subject: getEmailSubject(),
            source: 'email'
        })
    })
    .then(response => response.json())
    .then(data => {
        updateLinkAppearance(linkElement, url, data);
    })
    .catch(error => {
        console.error('PhishGuard scan failed:', error);
    });
}

// Scan image link
function scanImageLink(url, link, imageData) {
    fetch(PHISHGUARD_API, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            url: url,
            email: getSenderEmail(),
            subject: getEmailSubject(),
            source: 'email',
            image: imageData
        })
    })
    .then(response => response.json())
    .then(data => {
        const container = link.parentNode.querySelector('[data-url]');
        if (container) {
            updateImageAppearance(container, data);
        }
    })
    .catch(error => {
        console.error('Image scan failed:', error);
    });
}

// Update link appearance
function updateLinkAppearance(link, url, data) {
    if (!data || !data.status) return;

    if (data.status === 'dangerous' || data.is_phishing) {
        link.style.cssText = `
            color: #ff0040 !important;
            background: rgba(255, 0, 64, 0.1);
            padding: 2px 8px;
            border-radius: 4px;
            border-left: 4px solid #ff0040;
            text-decoration: line-through;
        `;
        link.title = '🚫 DANGEROUS - Phishing link blocked!';
        link.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            showPhishingWarning(url);
        }, true);
    } else if (data.status === 'suspicious') {
        link.style.cssText = `
            color: #ffaa00 !important;
            border-bottom: 3px dashed #ffaa00;
            padding: 2px 4px;
        `;
        link.title = '⚠️ SUSPICIOUS - Click with caution';
        link.addEventListener('click', (e) => {
            if (!confirm('⚠️ This link appears suspicious. Continue anyway?')) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);
    } else {
        link.style.cssText = `
            color: #00ff41 !important;
            border-bottom: 2px solid #00ff41;
            padding: 2px 4px;
        `;
        link.title = '✅ Safe link';
    }
}

// Update image appearance
function updateImageAppearance(container, data) {
    if (!data || !data.status) return;

    if (data.status === 'dangerous') {
        container.style.border = '3px solid #ff0040';
        container.style.borderRadius = '8px';
        container.style.boxShadow = '0 0 30px rgba(255, 0, 64, 0.3)';
        container.dataset.status = 'dangerous';
    } else if (data.status === 'suspicious') {
        container.style.border = '3px solid #ffaa00';
        container.style.borderRadius = '8px';
        container.style.boxShadow = '0 0 30px rgba(255, 170, 0, 0.3)';
        container.dataset.status = 'suspicious';
    } else {
        container.style.border = '2px solid #00ff41';
        container.style.borderRadius = '8px';
        container.style.boxShadow = '0 0 20px rgba(0, 255, 65, 0.2)';
        container.dataset.status = 'safe';
    }
}

// Get sender email
function getSenderEmail() {
    const senderElement = document.querySelector('.gD') ||
                         document.querySelector('[email]');
    if (senderElement) {
        return senderElement.getAttribute('email') || senderElement.textContent;
    }
    return null;
}

// Get email subject
function getEmailSubject() {
    const subjectElement = document.querySelector('h2.hP') ||
                          document.querySelector('.adn .hP');
    if (subjectElement) {
        return subjectElement.textContent;
    }
    return null;
}

// Show phishing warning
function showPhishingWarning(url) {
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.9);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Segoe UI', sans-serif;
    `;
    overlay.innerHTML = `
        <div style="
            background: #1a1a2e;
            padding: 40px;
            border-radius: 20px;
            max-width: 500px;
            text-align: center;
            border: 2px solid #ff0040;
            box-shadow: 0 0 60px rgba(255, 0, 64, 0.3);
        ">
            <div style="font-size: 4rem;">🚫</div>
            <h2 style="color: #ff0040; margin: 20px 0;">Phishing Link Detected!</h2>
            <p style="color: #ccc; margin-bottom: 20px;">
                This link appears to be a phishing attempt that may steal your personal information.
            </p>
            <div style="
                background: #0a0a0a;
                padding: 12px;
                border-radius: 8px;
                margin-bottom: 20px;
                word-break: break-all;
                font-size: 12px;
                color: #888;
            ">${url}</div>
            <button onclick="this.closest('div[style]').parentElement.remove()" style="
                background: #ff0040;
                color: white;
                border: none;
                padding: 12px 30px;
                border-radius: 8px;
                font-size: 16px;
                cursor: pointer;
                font-weight: 600;
            ">Return to Safety</button>
            <button onclick="window.open('${url}', '_blank'); this.closest('div[style]').parentElement.remove()" style="
                background: transparent;
                color: #666;
                border: 1px solid #666;
                padding: 12px 30px;
                border-radius: 8px;
                font-size: 16px;
                cursor: pointer;
                font-weight: 600;
                margin-left: 10px;
            ">Proceed Anyway</button>
        </div>
    `;
    document.body.appendChild(overlay);
}

// Start scanner
function startScanner() {
    setTimeout(scanEmailLinks, 2000);
    const observer = new MutationObserver(() => {
        setTimeout(scanEmailLinks, 500);
    });
    observer.observe(document.body, { childList: true, subtree: true });
}

if (document.readyState === 'complete') {
    startScanner();
} else {
    window.addEventListener('load', startScanner);
}

console.log('🛡️ PhishGuard Email Scanner active');
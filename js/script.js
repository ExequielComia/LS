<<<<<<< HEAD

// ── REGISTER ──
function switchToRegister() {
    document.getElementById('loginModal').classList.remove('active');
    document.getElementById('registerModal').classList.add('active');
}

function switchToLogin() {
    document.getElementById('registerModal')?.classList.remove('active');
    document.getElementById('forgotModal')?.classList.remove('active');
    document.getElementById('loginModal')?.classList.add('active');
}


// ── FORGOT PASSWORD ──
let otpCountdown    = null;
let resendCountdown = null;
let generatedOTP    = null;

function switchToForgot() {
    document.getElementById('loginModal').classList.remove('active');
    resetForgotSteps();
    document.getElementById('forgotModal').classList.add('active');
}

function closeForgot() {
    document.getElementById('forgotModal').classList.remove('active');
    clearInterval(otpCountdown);
    clearInterval(resendCountdown);
}

function resetForgotSteps() {
    for (let i = 1; i <= 4; i++) {
        const el = document.getElementById('forgot-step-' + i);
        if (el) el.style.display = i === 1 ? 'block' : 'none';
    }
    const fp = document.getElementById('forgot-phone');
    const fh = document.getElementById('forgot-phone-hint');
    const oe = document.getElementById('otp-error');
    if (fp) fp.value = '';
    if (fh) fh.textContent = '';
    if (oe) oe.textContent = '';
    getGBoxes().forEach(b => {
        if (!b) return;
        b.value        = '';
        b.style.border = '2px solid #e8dcc8';
        b.style.color  = '#3b2208';
    });
    clearInterval(otpCountdown);
    clearInterval(resendCountdown);
}

function showForgotStep(n) {
    for (let i = 1; i <= 4; i++) {
        const el = document.getElementById('forgot-step-' + i);
        if (el) el.style.display = i === n ? 'block' : 'none';
    }
}

function forgotFormatPhone(input) {
    let val = input.value.replace(/\D/g, '');
    if (val.length > 11) val = val.slice(0, 11);
    if (val.length > 7)      val = val.slice(0, 4) + '-' + val.slice(4, 7) + '-' + val.slice(7);
    else if (val.length > 4) val = val.slice(0, 4) + '-' + val.slice(4);
    input.value = val;
    const hint   = document.getElementById('forgot-phone-hint');
    const digits = val.replace(/\D/g, '');
    if (digits.length === 11 && val.startsWith('09')) {
        hint.textContent = '✓ Valid number'; hint.className = 'field-hint ok';
    } else if (val.length > 0) {
        hint.textContent = 'Must be 11 digits starting with 09'; hint.className = 'field-hint error';
    } else {
        hint.textContent = ''; hint.className = 'field-hint';
    }
}

function sendOTP() {
    const phone = document.getElementById('forgot-phone').value.replace(/\D/g, '');
    if (phone.length !== 11 || !phone.startsWith('09')) {
        alert('Please enter a valid PH mobile number.'); return;
    }
    generatedOTP = Math.floor(100000 + Math.random() * 900000).toString();
    console.log('OTP (dev only):', generatedOTP);
    alert('OTP sent to ' + document.getElementById('forgot-phone').value + '\n\n[DEV] Code: ' + generatedOTP);
    document.getElementById('forgot-phone-display').textContent = document.getElementById('forgot-phone').value;
    showForgotStep(2);
    startOTPTimer();
    startResendTimer();
}

function startOTPTimer() {
    clearInterval(otpCountdown);
    let secs = 120;
    const el = document.getElementById('otp-timer');
    if (!el) return;
    otpCountdown = setInterval(() => {
        secs--;
        const m = Math.floor(secs / 60), s = secs % 60;
        el.textContent = m + ':' + String(s).padStart(2, '0');
        el.style.color = 'saddlebrown';
        if (secs <= 0) {
            clearInterval(otpCountdown);
            el.textContent = 'Expired';
            el.style.color = '#c0392b';
        }
    }, 1000);
}

function startResendTimer() {
    clearInterval(resendCountdown);
    let secs  = 30;
    const btn = document.getElementById('resend-link');
    if (!btn) return;
    btn.disabled  = true;
    btn.innerHTML = 'Resend (<span id="resend-timer">30</span>s)';
    resendCountdown = setInterval(() => {
        secs--;
        const el = document.getElementById('resend-timer');
        if (el) el.textContent = secs;
        if (secs <= 0) {
            clearInterval(resendCountdown);
            btn.disabled    = false;
            btn.textContent = 'Resend code';
        }
    }, 1000);
}

// ── OTP BOXES ──
function getGBoxes() {
    return ['gb0', 'gb1', 'gb2', 'gb3', 'gb4', 'gb5'].map(id => document.getElementById(id));
}

function gcashInput(el, index) {
    el.value = el.value.replace(/\D/g, '').slice(-1);
    const boxes = getGBoxes();
    if (el.value) {
        el.style.border = '2px solid saddlebrown';
        el.style.color  = 'saddlebrown';
        if (index < 5 && boxes[index + 1]) boxes[index + 1].focus();
    } else {
        el.style.border = '2px solid #e8dcc8';
        el.style.color  = '#3b2208';
    }
    checkOTPComplete();
}

function gcashKey(el, index, e) {
    const boxes = getGBoxes();
    if (e.key === 'Backspace') {
        if (el.value) {
            el.value        = '';
            el.style.border = '2px solid #e8dcc8';
            el.style.color  = '#3b2208';
        } else if (index > 0 && boxes[index - 1]) {
            const prev        = boxes[index - 1];
            prev.value        = '';
            prev.style.border = '2px solid #e8dcc8';
            prev.style.color  = '#3b2208';
            prev.focus();
        }
        checkOTPComplete();
        e.preventDefault();
    }
    if (!/^\d$/.test(e.key) && !['Backspace', 'Tab', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
        e.preventDefault();
    }
}

function checkOTPComplete() {
    const done = getGBoxes().every(b => b && b.value.length === 1);
    const btn  = document.getElementById('verify-main-btn');
    if (btn) { btn.disabled = !done; btn.style.opacity = done ? '1' : '0.6'; }
}

function verifyOTP() {
    const boxes   = getGBoxes();
    const entered = boxes.map(b => b ? b.value : '').join('');
    const errEl   = document.getElementById('otp-error');

    if (entered.length < 6) {
        errEl.textContent = 'Please enter the complete 6-digit code.';
        errEl.className   = 'field-hint error'; return;
    }
    if (entered !== generatedOTP) {
        boxes.forEach(b => { if (b) { b.style.border = '2px solid #c0392b'; b.style.color = '#c0392b'; } });
        errEl.textContent = 'Incorrect code. Please try again.';
        errEl.className   = 'field-hint error';
        setTimeout(() => {
            boxes.forEach(b => { if (b) { b.value = ''; b.style.border = '2px solid #e8dcc8'; b.style.color = '#3b2208'; } });
            if (boxes[0]) boxes[0].focus();
            errEl.textContent = '';
            checkOTPComplete();
        }, 900);
        return;
    }
    clearInterval(otpCountdown);
    showForgotStep(3);
}

function resendOTP() {
    generatedOTP = Math.floor(100000 + Math.random() * 900000).toString();
    console.log('Resent OTP (dev only):', generatedOTP);
    alert('New OTP sent!\n\n[DEV] Code: ' + generatedOTP);
    getGBoxes().forEach(b => { if (b) { b.value = ''; b.style.border = '2px solid #e8dcc8'; b.style.color = '#3b2208'; } });
    const oe = document.getElementById('otp-error');
    if (oe) oe.textContent = '';
    startOTPTimer();
    startResendTimer();
}

// ── RESET PASSWORD ──
function checkForgotPassStrength(val) {
    const hint = document.getElementById('forgot-pass-hint');
    if (!hint) return;
    if (val.length === 0)     { hint.textContent = ''; return; }
    if (val.length < 6)       { hint.textContent = 'Too short'; hint.className = 'field-hint error'; }
    else if (val.length < 10) { hint.textContent = 'Moderate';  hint.className = 'field-hint'; }
    else                      { hint.textContent = '✓ Strong';  hint.className = 'field-hint ok'; }
}

function resetPassword() {
    const newPass = document.getElementById('forgot-newpass').value;
    const confirm = document.getElementById('forgot-confirmpass').value;
    if (!newPass || newPass.length < 6) { alert('Password must be at least 6 characters.'); return; }
    if (newPass !== confirm)            { alert('Passwords do not match.'); return; }
    clearInterval(otpCountdown);
    clearInterval(resendCountdown);
    showForgotStep(4);
}

// ── ORDER HISTORY ──
let currentReviewBtn  = null;
let currentRefundBtn  = null;
let currentCancelItem = null;
let selectedStar      = 0;

function filterOrders() {
    const filterEl = document.getElementById('filter-status');
    if (!filterEl) return;
    const val   = filterEl.value;
    const items = document.querySelectorAll('.oh-item');
    let visible = 0;
    items.forEach(item => {
        const match = val === 'all' || item.dataset.status === val;
        item.style.display = match ? 'flex' : 'none';
        if (match) visible++;
    });
    const emptyEl = document.getElementById('oh-empty');
    if (emptyEl) emptyEl.style.display = visible === 0 ? 'block' : 'none';
}

function writeReview(btn) {
    currentReviewBtn = btn;
    const item = btn.closest('.oh-item');
    document.getElementById('review-product-name').textContent = item.querySelector('.oh-name').textContent;
    setStar(0);
    document.getElementById('review-text').value = '';
    document.getElementById('review-modal').style.display = 'flex';
}

function setStar(n) {
    selectedStar = n;
    document.querySelectorAll('.star').forEach((s, i) => s.classList.toggle('active', i < n));
}

function submitReview() {
    const text = document.getElementById('review-text').value.trim();
    if (!selectedStar) { alert('Please select a star rating.'); return; }
    if (!text)         { alert('Please write a review.'); return; }
    alert('Review submitted! ' + selectedStar + '★\n"' + text + '"');
    closeModal('review-modal');
}

function requestRefund(btn) {
    currentRefundBtn = btn;
    const item = btn.closest('.oh-item');
    document.getElementById('refund-product-name').textContent = item.querySelector('.oh-name').textContent;
    document.getElementById('refund-notes').value = '';
    document.getElementById('refund-modal').style.display = 'flex';
}

function submitRefund() {
    const reason = document.getElementById('refund-reason').value;
    const notes  = document.getElementById('refund-notes').value.trim();
    alert('Refund requested!\nReason: ' + reason + (notes ? '\nNotes: ' + notes : ''));
    closeModal('refund-modal');
}

function cancelOrder(btn) {
    const item   = btn.closest('.oh-item');
    const status = item.dataset.status;
    if (status === 'Delivered') {
        alert('This order has already been delivered and cannot be cancelled.'); return;
    }
    if (status === 'Out for Delivery') {
        if (!confirm('This order is already out for delivery. Are you sure you want to request a cancellation?')) return;
    }
    currentCancelItem = item;
    document.getElementById('cancel-product-name').textContent = item.querySelector('.oh-name').textContent;
    document.getElementById('cancel-notes').value  = '';
    document.getElementById('cancel-reason').value = 'Changed my mind';
    document.getElementById('cancel-modal').style.display = 'flex';
}

function submitCancel() {
    const reason = document.getElementById('cancel-reason').value;
    const notes  = document.getElementById('cancel-notes').value.trim();
    if (currentCancelItem) {
        const badge = currentCancelItem.querySelector('.oh-status-badge');
        badge.textContent   = 'Cancelled';
        badge.className     = 'oh-status-badge';
        badge.style.cssText = 'background:#f8d7da; color:#721c24; border-color:#c0392b;';
        const cancelBtn = currentCancelItem.querySelector('.oh-btn.cancel');
        if (cancelBtn) cancelBtn.remove();
        currentCancelItem.style.opacity  = '0.6';
        currentCancelItem.dataset.status = 'Cancelled';
    }
    alert('Order cancelled.\nReason: ' + reason + (notes ? '\nNotes: ' + notes : ''));
    closeModal('cancel-modal');
    currentCancelItem = null;
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

document.querySelectorAll('.oh-modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function (e) {
        if (e.target === this) this.style.display = 'none';
    });
});

// ── RIDER MODAL ──
function handleRiderSubmit() {
    const firstName = document.getElementById('riderFirstName')?.value.trim();
    const lastName  = document.getElementById('riderLastName')?.value.trim();
    const email     = document.getElementById('riderEmail')?.value.trim();
    const phone     = document.getElementById('riderPhone')?.value.trim();
    const vehicle   = document.getElementById('riderVehicle')?.value;
    const password  = document.getElementById('riderPassword')?.value;
    const confirm   = document.getElementById('confirmriderPassword')?.value;
    const terms     = document.getElementById('riderTerms')?.checked;
    const idFile    = document.getElementById('riderIdFile')?.files[0];

    if (!firstName || !lastName || !email || !phone || !vehicle || !password) {
        alert('Please fill in all fields.'); return;
    }
    if (password !== confirm) {
        alert('Passwords do not match.'); return;
    }
    if (!idFile) {
        alert('Please upload a valid government ID.'); return;
    }
    if (!terms) {
        alert('Please agree to the Terms & Conditions.'); return;
    }

    alert('Application submitted! Welcome, ' + firstName + '!');
    document.getElementById('riderModal').classList.remove('active');
}

const riderModal = document.getElementById('riderModal');
if (riderModal) {
    riderModal.addEventListener('click', function (e) {
        if (e.target === this) this.classList.remove('active');
    });
}

// ── ID UPLOAD ──
function handleIdUpload(input) {
    const file = input.files[0];
    if (!file) return;
    validateAndPreviewId(file);
}

function handleIdDrop(e) {
    e.preventDefault();
    document.getElementById('id-drop-zone').style.borderColor = '#c8b89a';
    const file = e.dataTransfer.files[0];
    if (!file) return;
    validateAndPreviewId(file);
}

function validateAndPreviewId(file) {
    const hint        = document.getElementById('id-hint');
    const preview     = document.getElementById('id-preview-wrap');
    const previewImg  = document.getElementById('id-preview-img');
    const previewFile = document.getElementById('id-preview-file');

    const allowed = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    if (!allowed.includes(file.type)) {
        hint.textContent = 'Invalid file type. Please upload JPG, PNG, or PDF.';
        hint.className   = 'field-hint error'; return;
    }
    if (file.size > 5 * 1024 * 1024) {
        hint.textContent = 'File too large. Maximum size is 5MB.';
        hint.className   = 'field-hint error'; return;
    }

    hint.textContent      = '✓ ID uploaded: ' + file.name;
    hint.className        = 'field-hint ok';
    preview.style.display = 'block';

    if (file.type === 'application/pdf') {
        previewImg.style.display  = 'none';
        previewFile.style.display = 'block';
        previewFile.innerHTML     = '📄 ' + file.name;
    } else {
        const reader = new FileReader();
        reader.onload = e => {
            previewImg.src            = e.target.result;
            previewImg.style.display  = 'block';
            previewFile.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
}

function clearIdUpload() {
    document.getElementById('riderIdFile').value             = '';
    document.getElementById('id-preview-wrap').style.display = 'none';
    document.getElementById('id-preview-img').src            = '';
    document.getElementById('id-hint').textContent           = '';
    document.getElementById('id-hint').className             = 'field-hint';
}

// ── PRODUCTS ──
const products = [
    { id: 1,  cat: 'Cookie', name: 'Bischoco',         desc: 'Crispy chocolate-flavored biscuit, perfect with coffee.',      price: 25  },
    { id: 2,  cat: 'Bread',  name: 'Banana Bread',      desc: 'Moist and sweet loaf made with ripe bananas.',                 price: 30  },
    { id: 3,  cat: 'Pastry', name: 'Otap',              desc: 'Flaky, oval-shaped puff pastry with a sugary glaze.',          price: 20  },
    { id: 4,  cat: 'Pastry', name: 'Ensaymada',         desc: 'Soft, buttery pastry topped with cheese and sugar.',           price: 45  },
    { id: 5,  cat: 'Bread',  name: 'Pandesal',          desc: 'Classic Filipino bread roll, soft and lightly salted.',        price: 5   },
    { id: 6,  cat: 'Cake',   name: 'Ube Cake',          desc: 'Purple yam chiffon cake layered with ube halaya cream.',       price: 180 },
    { id: 7,  cat: 'Cookie', name: 'Polvoron',          desc: 'Crumbly milk candy made with toasted flour and sugar.',        price: 15  },
    { id: 8,  cat: 'Cake',   name: 'Leche Flan',        desc: 'Rich, creamy custard with a golden caramel topping.',         price: 60  },
    { id: 9,  cat: 'Drink',  name: 'Kapeng Barako',     desc: 'Bold and strong Filipino coffee from Batangas.',               price: 35  },
    { id: 10, cat: 'Drink',  name: "Sago't Gulaman",    desc: 'Sweet cold drink with tapioca pearls and jelly.',              price: 25  },
    { id: 11, cat: 'Pastry', name: 'Empanada',          desc: 'Crispy pastry filled with savory meat and vegetables.',        price: 30  },
    { id: 12, cat: 'Bread',  name: 'Malungay Pandesal', desc: 'Nutrient-rich pandesal packed with malungay leaves.',          price: 6   },
];

// ── FOOD GRID ──
function renderGrid(filter) {
    const grid = document.getElementById('foodGrid');
    if (!grid) return;
    const items = (!filter || filter === 'All') ? products : products.filter(p => p.cat === filter);
    grid.innerHTML = items.map(p => `
        <div class="food-card" data-id="${p.id}" data-name="${p.name}" data-price="${p.price}" data-cat="${p.cat}">
            <div class="food-img" style="background:#f3ece0; display:flex; align-items:center; justify-content:center; font-size:13px; color:#8b6340; font-family:sans-serif; height:130px;">${p.cat}</div>
            <div class="food-card-body">
                <span class="food-category">${p.cat}</span>
                <p class="food-name">${p.name}</p>
                <p class="food-desc">${p.desc}</p>
                <p class="food-price">₱${p.price}.00</p>
                <button class="add-btn" onclick="addToCart(this)">+ Add to cart</button>
            </div>
        </div>
    `).join('');
}

// ── CART ──
let cart = {};

function addToCart(btn) {
    const card  = btn.closest('.food-card');
    const id    = card.dataset.id;
    const name  = card.dataset.name;
    const price = parseFloat(card.dataset.price);
    cart[id]    = cart[id] || { name, price, qty: 0 };
    cart[id].qty++;
    renderCart();
}

function changeQty(id, delta) {
    if (!cart[id]) return;
    cart[id].qty += delta;
    if (cart[id].qty <= 0) delete cart[id];
    renderCart();
}

function renderCart() {
    const ids        = Object.keys(cart);
    const totalQty   = ids.reduce((s, id) => s + cart[id].qty, 0);
    const totalPrice = ids.reduce((s, id) => s + cart[id].price * cart[id].qty, 0);

    const badge       = document.getElementById('cartBadge');
    const totalEl     = document.getElementById('cartTotal');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const cartItems   = document.getElementById('cartItems');

    if (!badge) return;

    badge.textContent    = totalQty;
    totalEl.textContent  = '₱' + totalPrice.toFixed(2);
    checkoutBtn.disabled = ids.length === 0;

    if (ids.length === 0) {
        cartItems.innerHTML = '<p class="cart-empty">No items yet.<br>Add something to get started!</p>';
        return;
    }

    cartItems.innerHTML = ids.map(id => {
        const item = cart[id];
        return `
            <div class="cart-item">
                <span class="cart-item-name">${item.name}</span>
                <div class="qty-controls">
                    <button class="qty-btn" onclick="changeQty('${id}', -1)">−</button>
                    <span class="qty-num">${item.qty}</span>
                    <button class="qty-btn" onclick="changeQty('${id}', 1)">+</button>
                </div>
                <span class="cart-item-price">₱${(item.price * item.qty).toFixed(2)}</span>
            </div>
        `;
    }).join('');
}

// ── FILTER ──
const filterBar = document.querySelector('.filter-bar');
if (filterBar) filterBar.addEventListener('click', e => {
    if (!e.target.classList.contains('filter-btn')) return;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    e.target.classList.add('active');
    renderGrid(e.target.dataset.cat);
});

// ── CART BUTTONS ──
const clearBtn = document.getElementById('clearBtn');
if (clearBtn) clearBtn.addEventListener('click', () => { cart = {}; renderCart(); });

const checkoutBtn = document.getElementById('checkoutBtn');
if (checkoutBtn) checkoutBtn.addEventListener('click', () => {
    alert('Order placed! Thank you.');
    cart = {};
    renderCart();
});

// ── INIT ──
renderGrid('All');
=======

// ── REGISTER ──
function switchToRegister() {
    document.getElementById('loginModal').classList.remove('active');
    document.getElementById('registerModal').classList.add('active');
}

function switchToLogin() {
    document.getElementById('registerModal')?.classList.remove('active');
    document.getElementById('forgotModal')?.classList.remove('active');
    document.getElementById('loginModal')?.classList.add('active');
}


// ── FORGOT PASSWORD ──
let otpCountdown    = null;
let resendCountdown = null;
let generatedOTP    = null;

function switchToForgot() {
    document.getElementById('loginModal').classList.remove('active');
    resetForgotSteps();
    document.getElementById('forgotModal').classList.add('active');
}

function closeForgot() {
    document.getElementById('forgotModal').classList.remove('active');
    clearInterval(otpCountdown);
    clearInterval(resendCountdown);
}

function resetForgotSteps() {
    for (let i = 1; i <= 4; i++) {
        const el = document.getElementById('forgot-step-' + i);
        if (el) el.style.display = i === 1 ? 'block' : 'none';
    }
    const fp = document.getElementById('forgot-phone');
    const fh = document.getElementById('forgot-phone-hint');
    const oe = document.getElementById('otp-error');
    if (fp) fp.value = '';
    if (fh) fh.textContent = '';
    if (oe) oe.textContent = '';
    getGBoxes().forEach(b => {
        if (!b) return;
        b.value        = '';
        b.style.border = '2px solid #e8dcc8';
        b.style.color  = '#3b2208';
    });
    clearInterval(otpCountdown);
    clearInterval(resendCountdown);
}

function showForgotStep(n) {
    for (let i = 1; i <= 4; i++) {
        const el = document.getElementById('forgot-step-' + i);
        if (el) el.style.display = i === n ? 'block' : 'none';
    }
}

function forgotFormatPhone(input) {
    let val = input.value.replace(/\D/g, '');
    if (val.length > 11) val = val.slice(0, 11);
    if (val.length > 7)      val = val.slice(0, 4) + '-' + val.slice(4, 7) + '-' + val.slice(7);
    else if (val.length > 4) val = val.slice(0, 4) + '-' + val.slice(4);
    input.value = val;
    const hint   = document.getElementById('forgot-phone-hint');
    const digits = val.replace(/\D/g, '');
    if (digits.length === 11 && val.startsWith('09')) {
        hint.textContent = '✓ Valid number'; hint.className = 'field-hint ok';
    } else if (val.length > 0) {
        hint.textContent = 'Must be 11 digits starting with 09'; hint.className = 'field-hint error';
    } else {
        hint.textContent = ''; hint.className = 'field-hint';
    }
}

function sendOTP() {
    const phone = document.getElementById('forgot-phone').value.replace(/\D/g, '');
    if (phone.length !== 11 || !phone.startsWith('09')) {
        alert('Please enter a valid PH mobile number.'); return;
    }
    generatedOTP = Math.floor(100000 + Math.random() * 900000).toString();
    console.log('OTP (dev only):', generatedOTP);
    alert('OTP sent to ' + document.getElementById('forgot-phone').value + '\n\n[DEV] Code: ' + generatedOTP);
    document.getElementById('forgot-phone-display').textContent = document.getElementById('forgot-phone').value;
    showForgotStep(2);
    startOTPTimer();
    startResendTimer();
}

function startOTPTimer() {
    clearInterval(otpCountdown);
    let secs = 120;
    const el = document.getElementById('otp-timer');
    if (!el) return;
    otpCountdown = setInterval(() => {
        secs--;
        const m = Math.floor(secs / 60), s = secs % 60;
        el.textContent = m + ':' + String(s).padStart(2, '0');
        el.style.color = 'saddlebrown';
        if (secs <= 0) {
            clearInterval(otpCountdown);
            el.textContent = 'Expired';
            el.style.color = '#c0392b';
        }
    }, 1000);
}

function startResendTimer() {
    clearInterval(resendCountdown);
    let secs  = 30;
    const btn = document.getElementById('resend-link');
    if (!btn) return;
    btn.disabled  = true;
    btn.innerHTML = 'Resend (<span id="resend-timer">30</span>s)';
    resendCountdown = setInterval(() => {
        secs--;
        const el = document.getElementById('resend-timer');
        if (el) el.textContent = secs;
        if (secs <= 0) {
            clearInterval(resendCountdown);
            btn.disabled    = false;
            btn.textContent = 'Resend code';
        }
    }, 1000);
}

// ── OTP BOXES ──
function getGBoxes() {
    return ['gb0', 'gb1', 'gb2', 'gb3', 'gb4', 'gb5'].map(id => document.getElementById(id));
}

function gcashInput(el, index) {
    el.value = el.value.replace(/\D/g, '').slice(-1);
    const boxes = getGBoxes();
    if (el.value) {
        el.style.border = '2px solid saddlebrown';
        el.style.color  = 'saddlebrown';
        if (index < 5 && boxes[index + 1]) boxes[index + 1].focus();
    } else {
        el.style.border = '2px solid #e8dcc8';
        el.style.color  = '#3b2208';
    }
    checkOTPComplete();
}

function gcashKey(el, index, e) {
    const boxes = getGBoxes();
    if (e.key === 'Backspace') {
        if (el.value) {
            el.value        = '';
            el.style.border = '2px solid #e8dcc8';
            el.style.color  = '#3b2208';
        } else if (index > 0 && boxes[index - 1]) {
            const prev        = boxes[index - 1];
            prev.value        = '';
            prev.style.border = '2px solid #e8dcc8';
            prev.style.color  = '#3b2208';
            prev.focus();
        }
        checkOTPComplete();
        e.preventDefault();
    }
    if (!/^\d$/.test(e.key) && !['Backspace', 'Tab', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
        e.preventDefault();
    }
}

function checkOTPComplete() {
    const done = getGBoxes().every(b => b && b.value.length === 1);
    const btn  = document.getElementById('verify-main-btn');
    if (btn) { btn.disabled = !done; btn.style.opacity = done ? '1' : '0.6'; }
}

function verifyOTP() {
    const boxes   = getGBoxes();
    const entered = boxes.map(b => b ? b.value : '').join('');
    const errEl   = document.getElementById('otp-error');

    if (entered.length < 6) {
        errEl.textContent = 'Please enter the complete 6-digit code.';
        errEl.className   = 'field-hint error'; return;
    }
    if (entered !== generatedOTP) {
        boxes.forEach(b => { if (b) { b.style.border = '2px solid #c0392b'; b.style.color = '#c0392b'; } });
        errEl.textContent = 'Incorrect code. Please try again.';
        errEl.className   = 'field-hint error';
        setTimeout(() => {
            boxes.forEach(b => { if (b) { b.value = ''; b.style.border = '2px solid #e8dcc8'; b.style.color = '#3b2208'; } });
            if (boxes[0]) boxes[0].focus();
            errEl.textContent = '';
            checkOTPComplete();
        }, 900);
        return;
    }
    clearInterval(otpCountdown);
    showForgotStep(3);
}

function resendOTP() {
    generatedOTP = Math.floor(100000 + Math.random() * 900000).toString();
    console.log('Resent OTP (dev only):', generatedOTP);
    alert('New OTP sent!\n\n[DEV] Code: ' + generatedOTP);
    getGBoxes().forEach(b => { if (b) { b.value = ''; b.style.border = '2px solid #e8dcc8'; b.style.color = '#3b2208'; } });
    const oe = document.getElementById('otp-error');
    if (oe) oe.textContent = '';
    startOTPTimer();
    startResendTimer();
}

// ── RESET PASSWORD ──
function checkForgotPassStrength(val) {
    const hint = document.getElementById('forgot-pass-hint');
    if (!hint) return;
    if (val.length === 0)     { hint.textContent = ''; return; }
    if (val.length < 6)       { hint.textContent = 'Too short'; hint.className = 'field-hint error'; }
    else if (val.length < 10) { hint.textContent = 'Moderate';  hint.className = 'field-hint'; }
    else                      { hint.textContent = '✓ Strong';  hint.className = 'field-hint ok'; }
}

function resetPassword() {
    const newPass = document.getElementById('forgot-newpass').value;
    const confirm = document.getElementById('forgot-confirmpass').value;
    if (!newPass || newPass.length < 6) { alert('Password must be at least 6 characters.'); return; }
    if (newPass !== confirm)            { alert('Passwords do not match.'); return; }
    clearInterval(otpCountdown);
    clearInterval(resendCountdown);
    showForgotStep(4);
}

// ── ORDER HISTORY ──
let currentReviewBtn  = null;
let currentRefundBtn  = null;
let currentCancelItem = null;
let selectedStar      = 0;

function filterOrders() {
    const filterEl = document.getElementById('filter-status');
    if (!filterEl) return;
    const val   = filterEl.value;
    const items = document.querySelectorAll('.oh-item');
    let visible = 0;
    items.forEach(item => {
        const match = val === 'all' || item.dataset.status === val;
        item.style.display = match ? 'flex' : 'none';
        if (match) visible++;
    });
    const emptyEl = document.getElementById('oh-empty');
    if (emptyEl) emptyEl.style.display = visible === 0 ? 'block' : 'none';
}

function writeReview(btn) {
    currentReviewBtn = btn;
    const item = btn.closest('.oh-item');
    document.getElementById('review-product-name').textContent = item.querySelector('.oh-name').textContent;
    setStar(0);
    document.getElementById('review-text').value = '';
    document.getElementById('review-modal').style.display = 'flex';
}

function setStar(n) {
    selectedStar = n;
    document.querySelectorAll('.star').forEach((s, i) => s.classList.toggle('active', i < n));
}

function submitReview() {
    const text = document.getElementById('review-text').value.trim();
    if (!selectedStar) { alert('Please select a star rating.'); return; }
    if (!text)         { alert('Please write a review.'); return; }
    alert('Review submitted! ' + selectedStar + '★\n"' + text + '"');
    closeModal('review-modal');
}

function requestRefund(btn) {
    currentRefundBtn = btn;
    const item = btn.closest('.oh-item');
    document.getElementById('refund-product-name').textContent = item.querySelector('.oh-name').textContent;
    document.getElementById('refund-notes').value = '';
    document.getElementById('refund-modal').style.display = 'flex';
}

function submitRefund() {
    const reason = document.getElementById('refund-reason').value;
    const notes  = document.getElementById('refund-notes').value.trim();
    alert('Refund requested!\nReason: ' + reason + (notes ? '\nNotes: ' + notes : ''));
    closeModal('refund-modal');
}

function cancelOrder(btn) {
    const item   = btn.closest('.oh-item');
    const status = item.dataset.status;
    if (status === 'Delivered') {
        alert('This order has already been delivered and cannot be cancelled.'); return;
    }
    if (status === 'Out for Delivery') {
        if (!confirm('This order is already out for delivery. Are you sure you want to request a cancellation?')) return;
    }
    currentCancelItem = item;
    document.getElementById('cancel-product-name').textContent = item.querySelector('.oh-name').textContent;
    document.getElementById('cancel-notes').value  = '';
    document.getElementById('cancel-reason').value = 'Changed my mind';
    document.getElementById('cancel-modal').style.display = 'flex';
}

function submitCancel() {
    const reason = document.getElementById('cancel-reason').value;
    const notes  = document.getElementById('cancel-notes').value.trim();
    if (currentCancelItem) {
        const badge = currentCancelItem.querySelector('.oh-status-badge');
        badge.textContent   = 'Cancelled';
        badge.className     = 'oh-status-badge';
        badge.style.cssText = 'background:#f8d7da; color:#721c24; border-color:#c0392b;';
        const cancelBtn = currentCancelItem.querySelector('.oh-btn.cancel');
        if (cancelBtn) cancelBtn.remove();
        currentCancelItem.style.opacity  = '0.6';
        currentCancelItem.dataset.status = 'Cancelled';
    }
    alert('Order cancelled.\nReason: ' + reason + (notes ? '\nNotes: ' + notes : ''));
    closeModal('cancel-modal');
    currentCancelItem = null;
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

document.querySelectorAll('.oh-modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function (e) {
        if (e.target === this) this.style.display = 'none';
    });
});

// ── RIDER MODAL ──
function handleRiderSubmit() {
    const firstName = document.getElementById('riderFirstName')?.value.trim();
    const lastName  = document.getElementById('riderLastName')?.value.trim();
    const email     = document.getElementById('riderEmail')?.value.trim();
    const phone     = document.getElementById('riderPhone')?.value.trim();
    const vehicle   = document.getElementById('riderVehicle')?.value;
    const password  = document.getElementById('riderPassword')?.value;
    const confirm   = document.getElementById('confirmriderPassword')?.value;
    const terms     = document.getElementById('riderTerms')?.checked;
    const idFile    = document.getElementById('riderIdFile')?.files[0];

    if (!firstName || !lastName || !email || !phone || !vehicle || !password) {
        alert('Please fill in all fields.'); return;
    }
    if (password !== confirm) {
        alert('Passwords do not match.'); return;
    }
    if (!idFile) {
        alert('Please upload a valid government ID.'); return;
    }
    if (!terms) {
        alert('Please agree to the Terms & Conditions.'); return;
    }

    alert('Application submitted! Welcome, ' + firstName + '!');
    document.getElementById('riderModal').classList.remove('active');
}

const riderModal = document.getElementById('riderModal');
if (riderModal) {
    riderModal.addEventListener('click', function (e) {
        if (e.target === this) this.classList.remove('active');
    });
}

// ── ID UPLOAD ──
function handleIdUpload(input) {
    const file = input.files[0];
    if (!file) return;
    validateAndPreviewId(file);
}

function handleIdDrop(e) {
    e.preventDefault();
    document.getElementById('id-drop-zone').style.borderColor = '#c8b89a';
    const file = e.dataTransfer.files[0];
    if (!file) return;
    validateAndPreviewId(file);
}

function validateAndPreviewId(file) {
    const hint        = document.getElementById('id-hint');
    const preview     = document.getElementById('id-preview-wrap');
    const previewImg  = document.getElementById('id-preview-img');
    const previewFile = document.getElementById('id-preview-file');

    const allowed = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    if (!allowed.includes(file.type)) {
        hint.textContent = 'Invalid file type. Please upload JPG, PNG, or PDF.';
        hint.className   = 'field-hint error'; return;
    }
    if (file.size > 5 * 1024 * 1024) {
        hint.textContent = 'File too large. Maximum size is 5MB.';
        hint.className   = 'field-hint error'; return;
    }

    hint.textContent      = '✓ ID uploaded: ' + file.name;
    hint.className        = 'field-hint ok';
    preview.style.display = 'block';

    if (file.type === 'application/pdf') {
        previewImg.style.display  = 'none';
        previewFile.style.display = 'block';
        previewFile.innerHTML     = '📄 ' + file.name;
    } else {
        const reader = new FileReader();
        reader.onload = e => {
            previewImg.src            = e.target.result;
            previewImg.style.display  = 'block';
            previewFile.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
}

function clearIdUpload() {
    document.getElementById('riderIdFile').value             = '';
    document.getElementById('id-preview-wrap').style.display = 'none';
    document.getElementById('id-preview-img').src            = '';
    document.getElementById('id-hint').textContent           = '';
    document.getElementById('id-hint').className             = 'field-hint';
}

// ── PRODUCTS ──
const products = [
    { id: 1,  cat: 'Cookie', name: 'Bischoco',         desc: 'Crispy chocolate-flavored biscuit, perfect with coffee.',      price: 25  },
    { id: 2,  cat: 'Bread',  name: 'Banana Bread',      desc: 'Moist and sweet loaf made with ripe bananas.',                 price: 30  },
    { id: 3,  cat: 'Pastry', name: 'Otap',              desc: 'Flaky, oval-shaped puff pastry with a sugary glaze.',          price: 20  },
    { id: 4,  cat: 'Pastry', name: 'Ensaymada',         desc: 'Soft, buttery pastry topped with cheese and sugar.',           price: 45  },
    { id: 5,  cat: 'Bread',  name: 'Pandesal',          desc: 'Classic Filipino bread roll, soft and lightly salted.',        price: 5   },
    { id: 6,  cat: 'Cake',   name: 'Ube Cake',          desc: 'Purple yam chiffon cake layered with ube halaya cream.',       price: 180 },
    { id: 7,  cat: 'Cookie', name: 'Polvoron',          desc: 'Crumbly milk candy made with toasted flour and sugar.',        price: 15  },
    { id: 8,  cat: 'Cake',   name: 'Leche Flan',        desc: 'Rich, creamy custard with a golden caramel topping.',         price: 60  },
    { id: 9,  cat: 'Drink',  name: 'Kapeng Barako',     desc: 'Bold and strong Filipino coffee from Batangas.',               price: 35  },
    { id: 10, cat: 'Drink',  name: "Sago't Gulaman",    desc: 'Sweet cold drink with tapioca pearls and jelly.',              price: 25  },
    { id: 11, cat: 'Pastry', name: 'Empanada',          desc: 'Crispy pastry filled with savory meat and vegetables.',        price: 30  },
    { id: 12, cat: 'Bread',  name: 'Malungay Pandesal', desc: 'Nutrient-rich pandesal packed with malungay leaves.',          price: 6   },
];

// ── FOOD GRID ──
function renderGrid(filter) {
    const grid = document.getElementById('foodGrid');
    if (!grid) return;
    const items = (!filter || filter === 'All') ? products : products.filter(p => p.cat === filter);
    grid.innerHTML = items.map(p => `
        <div class="food-card" data-id="${p.id}" data-name="${p.name}" data-price="${p.price}" data-cat="${p.cat}">
            <div class="food-img" style="background:#f3ece0; display:flex; align-items:center; justify-content:center; font-size:13px; color:#8b6340; font-family:sans-serif; height:130px;">${p.cat}</div>
            <div class="food-card-body">
                <span class="food-category">${p.cat}</span>
                <p class="food-name">${p.name}</p>
                <p class="food-desc">${p.desc}</p>
                <p class="food-price">₱${p.price}.00</p>
                <button class="add-btn" onclick="addToCart(this)">+ Add to cart</button>
            </div>
        </div>
    `).join('');
}

// ── CART ──
let cart = {};

function addToCart(btn) {
    const card  = btn.closest('.food-card');
    const id    = card.dataset.id;
    const name  = card.dataset.name;
    const price = parseFloat(card.dataset.price);
    cart[id]    = cart[id] || { name, price, qty: 0 };
    cart[id].qty++;
    renderCart();
}

function changeQty(id, delta) {
    if (!cart[id]) return;
    cart[id].qty += delta;
    if (cart[id].qty <= 0) delete cart[id];
    renderCart();
}

function renderCart() {
    const ids        = Object.keys(cart);
    const totalQty   = ids.reduce((s, id) => s + cart[id].qty, 0);
    const totalPrice = ids.reduce((s, id) => s + cart[id].price * cart[id].qty, 0);

    const badge       = document.getElementById('cartBadge');
    const totalEl     = document.getElementById('cartTotal');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const cartItems   = document.getElementById('cartItems');

    if (!badge) return;

    badge.textContent    = totalQty;
    totalEl.textContent  = '₱' + totalPrice.toFixed(2);
    checkoutBtn.disabled = ids.length === 0;

    if (ids.length === 0) {
        cartItems.innerHTML = '<p class="cart-empty">No items yet.<br>Add something to get started!</p>';
        return;
    }

    cartItems.innerHTML = ids.map(id => {
        const item = cart[id];
        return `
            <div class="cart-item">
                <span class="cart-item-name">${item.name}</span>
                <div class="qty-controls">
                    <button class="qty-btn" onclick="changeQty('${id}', -1)">−</button>
                    <span class="qty-num">${item.qty}</span>
                    <button class="qty-btn" onclick="changeQty('${id}', 1)">+</button>
                </div>
                <span class="cart-item-price">₱${(item.price * item.qty).toFixed(2)}</span>
            </div>
        `;
    }).join('');
}

// ── FILTER ──
const filterBar = document.querySelector('.filter-bar');
if (filterBar) filterBar.addEventListener('click', e => {
    if (!e.target.classList.contains('filter-btn')) return;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    e.target.classList.add('active');
    renderGrid(e.target.dataset.cat);
});

// ── CART BUTTONS ──
const clearBtn = document.getElementById('clearBtn');
if (clearBtn) clearBtn.addEventListener('click', () => { cart = {}; renderCart(); });

const checkoutBtn = document.getElementById('checkoutBtn');
if (checkoutBtn) checkoutBtn.addEventListener('click', () => {
    alert('Order placed! Thank you.');
    cart = {};
    renderCart();
});

// ── INIT ──
renderGrid('All');
>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
renderCart();
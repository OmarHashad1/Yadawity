document.addEventListener('DOMContentLoaded', function() {
    fetch('API/getCart.php', { credentials: 'include' })
        .then(res => res.json())
        .then(data => {
            console.log('Checkout API response:', data);
            if (data.success && data.data) {
                renderCheckoutItems(data.data.cart_items);
                updateSummary({
                    total: data.data.cart_summary.subtotal,
                    shipping: 50,
                    discount: 0
                });
            } else {
                document.getElementById('checkout-items').textContent = 'Failed to load checkout data.';
            }
        });

    document.getElementById('checkout-btn').addEventListener('click', function() {
        // Validate form
        const form = document.getElementById('shipping-form');
        if (!form.reportValidity() || !document.getElementById('terms').checked) {
            document.getElementById('checkout-message').textContent = 'Please fill all required fields and agree to the terms.';
            return;
        }
        // Collect form data
        const payment = document.querySelector('input[name="payment"]:checked').value;
        const shippingType = document.querySelector('input[name="shippingType"]:checked').value;
        const payload = {
            fullName: document.getElementById('fullName').value,
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value,
            country: document.getElementById('country').value,
            city: document.getElementById('city').value,
            state: document.getElementById('state').value,
            zip: document.getElementById('zip').value,
            payment,
            shippingType
        };
        fetch('API/placeOrder.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(res => res.json())
            .then(data => {
                document.getElementById('checkout-message').textContent = data.message;
                if (data.success) {
                    document.getElementById('checkout-items').innerHTML = '';
                    updateSummary({subtotal:0, shipping:0, discount:0, total:0});
                }
            });
    });

    document.getElementById('apply-discount').addEventListener('click', function() {
        // Demo: just show discount applied
        document.getElementById('discount-val').textContent = '-$10.00';
        let subtotal = parseFloat(document.getElementById('subtotal-val').textContent.replace('$',''));
        let shipping = parseFloat(document.getElementById('shipping-val').textContent.replace('$',''));
        let total = subtotal + shipping - 10;
        document.getElementById('checkout-total').textContent = `$${total.toFixed(2)}`;
    });
});

function fillShippingForm(user) {
    if (!user) return;
    document.getElementById('fullName').value = user.name || '';
    document.getElementById('email').value = user.email || '';
    document.getElementById('phone').value = user.phone || '';
    document.getElementById('country').value = user.country || '';
    // Optionally parse address for city/state/zip
}

function renderCheckoutItems(items) {
    const container = document.getElementById('checkout-items');
    container.innerHTML = '';
    if (!items.length) {
        container.textContent = 'Your cart is empty.';
        return;
    }
    items.forEach(item => {
        const price = item.artwork && item.artwork.price ? item.artwork.price : 0;
        const name = item.artwork && item.artwork.title ? item.artwork.title : 'Artwork';
        const image = item.artwork && item.artwork.artwork_image_url ? item.artwork.artwork_image_url : '';
        const artist = item.artist && item.artist.full_name ? item.artist.full_name : '';
        const div = document.createElement('div');
        div.className = 'checkout-item';
        div.innerHTML = `
            <div style="display:flex;align-items:center;gap:10px;">
                <img src="${image}" alt="Artwork" style="width:48px;height:48px;object-fit:cover;border-radius:6px;border:1px solid #eee;">
                <div>
                    <div style="font-weight:600;">${name}</div>
                    <div style="font-size:0.95em;color:#888;">by ${artist}</div>
                </div>
            </div>
            <span>${item.quantity}x</span>
            <span>$${price.toFixed(2)}</span>
        `;
        div.style.display = 'flex';
        div.style.justifyContent = 'space-between';
        div.style.alignItems = 'center';
        div.style.padding = '10px 0';
        div.style.borderBottom = '1px solid #f0f0f0';
        container.appendChild(div);
    });
}

function updateSummary(data) {
    document.getElementById('subtotal-val').textContent = data.total ? `$${data.total.toFixed(2)}` : '$0.00';
    document.getElementById('shipping-val').textContent = data.shipping ? `$${data.shipping.toFixed(2)}` : '$50.00';
    let subtotal = data.total || 0;
    let shipping = data.shipping || 50;
    let total = subtotal + shipping;
    document.getElementById('checkout-total').textContent = `$${total.toFixed(2)}`;
}

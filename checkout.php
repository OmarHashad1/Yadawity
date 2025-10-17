<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/x-icon" href="./image/YAD-05.ico">
     <meta name="description" content="Find and book your perfect local art gallery experience. Explore physical galleries, meet artists, and immerse yourself in local art culture with Yadawity.">
    <meta name="keywords" content="local art gallery, art galleries, in-person art, local artists, art tours, art events, book gallery, Yadawity">
    <meta name="author" content="Yadawity">
    <meta property="og:title" content="Yadawity - Local Galleries">
    <meta property="og:description" content="Find and book your perfect local art gallery experience. Explore physical galleries, meet artists, and immerse yourself in local art culture with Yadawity.">
    <meta property="og:image" content="/image/darker_image_25_percent.jpeg">
    <meta property="og:type" content="website">
    <meta property="og:url" content="http://localhost/localGallery.php">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Yadawity - Local Galleries">
    <meta name="twitter:description" content="Find and book your perfect local art gallery experience. Explore physical galleries, meet artists, and immerse yourself in local art culture with Yadawity.">
    <meta name="twitter:image" content="/image/darker_image_25_percent.jpeg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="./public/checkout.css">
</head>
<body>
    <div class="checkout-main">
        <div class="checkout-left">
            <h1>Checkout</h1>
            <form id="shipping-form">
                <div class="shipping-methods">
                    <label class="radio-btn"><input type="radio" name="shippingType" value="delivery" checked> <span>Delivery</span></label>
                    <label class="radio-btn"><input type="radio" name="shippingType" value="pickup"> <span>Pick up</span></label>
                </div>
                <div class="form-group">
                    <label>Full name *</label>
                    <input type="text" id="fullName" name="fullName" required>
                </div>
                <div class="form-group">
                    <label>Email address *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Phone number *</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label>Country *</label>
                    <input type="text" id="country" name="country" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" id="city" name="city">
                    </div>
                    <div class="form-group">
                        <label>State</label>
                        <input type="text" id="state" name="state">
                    </div>
                    <div class="form-group">
                        <label>ZIP Code</label>
                        <input type="text" id="zip" name="zip">
                    </div>
                </div>
                <div class="form-group terms">
                    <label><input type="checkbox" id="terms" required> I have read and agree to the Terms and Conditions.</label>
                </div>
            </form>
        </div>
        <div class="checkout-right">
            <h2>Review your cart</h2>
            <div id="checkout-items"></div>
            <div class="summary-row"><span>Subtotal</span><span id="subtotal-val">$0.00</span></div>
            <div class="summary-row"><span>Shipping</span><span id="shipping-val">$50.00</span></div>
            <div class="summary-row total"><span>Total</span><span id="checkout-total">$0.00</span></div>
            <div class="form-group">
                <label>Payment Method</label>
                <div class="payment-methods payment-icons">
                    <label><input type="radio" name="payment" value="cash" checked> <img src="./image/cash on delivery.png" alt="Cash on Delivery" class="paymentLogo"> Cash on Delivery</label><br>
                    <label><input type="radio" name="payment" value="vodafone"> <img src="./image/vodafone cash.jpg" alt="Vodafone Cash" class="paymentLogo"> Vodafone Cash</label><br>
                    <label><input type="radio" name="payment" value="instapay"> <img src="./image/InstaPay.png" alt="InstaPay" class="paymentLogo"> InstaPay</label><br>
                    <label><input type="radio" name="payment" value="meeza"> <img src="./image/meeza.png" alt="Meeza" class="paymentLogo"> ميزا (Meeza)</label><br>
                    <label><input type="radio" name="payment" value="visa"> <img src="./image/Visa .svg" alt="Visa" class="paymentLogo"> Visa</label>
                </div>
            </div>
            <button id="checkout-btn">Pay Now</button>
            <div class="secure-row">
                <span class="lock-icon">🔒</span> Secure Checkout – SSL Encrypted
            </div>
            <div class="secure-desc">Ensuring your financial and personal details are secure during every transaction.</div>
            <div id="checkout-message"></div>
        </div>
    </div>
    <script src="./public/checkout.js"></script>
    <script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/68ad7c376c34a5192ea60d8f/1j3iqqep5';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
</body>
</html>

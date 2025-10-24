// Bilet Satın Alma Sistemi - JavaScript

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 App initialized');
    
    // Seat selection functionality
    initSeatSelection();
    
    // Auto-hide flash messages after 5 seconds
    autoHideFlashMessages();
});

function initSeatSelection() {
    // Support both old .seat and new .bus-seat selectors
    const seats = document.querySelectorAll('.seat.available, .bus-seat.available');
    const selectedSeatInput = document.getElementById('selected-seat');
    const selectedInfo = document.getElementById('selected-info');
    const seatDisplay = document.getElementById('seat-display');
    
    console.log('🪑 Found seats:', seats.length);
    console.log('📋 Form elements:', {
        selectedSeatInput: !!selectedSeatInput,
        selectedInfo: !!selectedInfo,
        seatDisplay: !!seatDisplay
    });
    
    if (!seats.length) {
        console.warn('⚠️ No available seats found!');
        return;
    }
    
    seats.forEach(seat => {
        seat.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('✅ Seat clicked:', this.dataset.seat);
            
            // Remove previous selection
            seats.forEach(s => s.classList.remove('selected'));
            
            // Add selection to clicked seat
            this.classList.add('selected');
            
            // Update form
            const seatNumber = this.dataset.seat;
            if (selectedSeatInput) selectedSeatInput.value = seatNumber;
            if (seatDisplay) seatDisplay.textContent = seatNumber;
            if (selectedInfo) selectedInfo.style.display = 'block';
            
            console.log('🎯 Selected seat:', seatNumber);
        });
    });
}

// Update seats based on bus type (for firm admin trip creation)
function updateSeatsBasedOnBusType() {
    const busTypeSelect = document.getElementById('bus_type');
    const seatsInput = document.getElementById('seats');
    const seatsHint = document.getElementById('seats-hint');
    
    if (!busTypeSelect || !seatsInput) return;
    
    const busType = busTypeSelect.value;
    const recommendedSeats = {
        '2+1': 36,
        '2+2': 44,
        '3+2': 45
    };
    
    if (recommendedSeats[busType]) {
        seatsInput.value = recommendedSeats[busType];
        seatsHint.textContent = `${busType} otobüs için önerilen: ${recommendedSeats[busType]}`;
    }
}

function autoHideFlashMessages() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
}

// Coupon validation (optional enhancement)
function validateCoupon() {
    const couponCode = document.getElementById('coupon_code').value;
    const firmaId = document.querySelector('input[name="trip_id"]')?.dataset.firmaId;
    
    if (!couponCode) return;
    
    fetch('/coupons/validate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `code=${encodeURIComponent(couponCode)}&firma_id=${firmaId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.valid) {
            alert(data.message);
        } else {
            alert('Kupon geçersiz: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}


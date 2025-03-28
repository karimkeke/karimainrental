// بيانات المنتجات المميزة
const featuredItems = [
    {
        id: 1,
        name: 'صالون جزائري عصري',
        image: 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc',
        price: '3000 دج/يوم',
        description: 'صالون عصري فخم مناسب للمناسبات والأعراس'
    },
    {
        id: 2,
        name: 'طاولة طعام كبيرة',
        image: 'https://images.unsplash.com/photo-1615066390971-03e4e1c36ddf',
        price: '2500 دج/يوم',
        description: 'طاولة طعام تتسع لـ 10 أشخاص مع كراسي'
    },
    {
        id: 3,
        name: 'غرفة نوم كاملة',
        image: 'https://images.unsplash.com/photo-1505693314120-0d443867891c',
        price: '5000 دج/يوم',
        description: 'غرفة نوم كاملة تشمل سرير وخزانة وتسريحة'
    }
];

// إضافة المنتجات المميزة للصفحة
function loadFeaturedItems() {
    const featuredSlider = document.querySelector('.featured-slider');
    if (!featuredSlider) return;
    
    featuredItems.forEach(item => {
        const itemCard = document.createElement('div');
        itemCard.className = 'featured-item fade-in';
        
        itemCard.innerHTML = `
            <div class="featured-item-inner">
                <img src="${item.image}" alt="${item.name}" loading="lazy">
                <div class="featured-item-content">
                    <h3>${item.name}</h3>
                    <p class="price">${item.price}</p>
                    <p class="description">${item.description}</p>
                    <button onclick="rentItem(${item.id})" class="rent-button">احجز الآن</button>
                </div>
            </div>
        `;
        
        featuredSlider.appendChild(itemCard);
    });
}

// تفعيل أزرار التصنيف
const tabButtons = document.querySelectorAll('.tab-button');
const productCards = document.querySelectorAll('.product-card');

tabButtons.forEach(button => {
    button.addEventListener('click', () => {
        // إزالة الفئة النشطة من جميع الأزرار
        tabButtons.forEach(btn => btn.classList.remove('active'));
        // إضافة الفئة النشطة للزر المحدد
        button.classList.add('active');
        
        const category = button.dataset.category;
        
        // تصفية المنتجات
        productCards.forEach(card => {
            if (category === 'all' || card.dataset.category === category) {
                card.style.display = 'block';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 10);
            } else {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.display = 'none';
                }, 300);
            }
        });
    });
});

// دالة حجز المنتج
function rentItem(button) {
    const card = button.closest('.product-card');
    const productName = card.querySelector('h3').textContent;
    const productPrice = card.querySelector('.price').textContent;
    
    showNotification(`Preparing your booking request for ${productName}...`, 'success');
    
    setTimeout(() => {
        const message = `Do you want to book ${productName}?\nPrice: ${productPrice}`;
        if (confirm(message)) {
            showNotification('We will contact you shortly to confirm the booking', 'success');
        }
    }, 500);
}

// تفعيل نموذج الاتصال مع التحقق من المدخلات
const contactForm = document.getElementById('contact-form');
if (contactForm) {
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const city = document.getElementById('city').value;
        const message = document.getElementById('message').value.trim();
        
        // التحقق من المدخلات
        if (name.length < 3) {
            showNotification('Please enter a valid name');
            return;
        }
        
        if (!isValidEmail(email)) {
            showNotification('Please enter a valid email address');
            return;
        }
        
        if (!isValidPhone(phone)) {
            showNotification('Please enter a valid phone number');
            return;
        }

        if (!city) {
            showNotification('Please select a city');
            return;
        }
        
        if (message.length < 10) {
            showNotification('Please enter a longer message');
            return;
        }
        
        showNotification('Your message has been received successfully! We will get back to you shortly', 'success');
        this.reset();
    });
}

// دوال مساعدة
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPhone(phone) {
    // التحقق من صيغة رقم الهاتف الجزائري
    return /^(0)(5|6|7)[0-9]{8}$/.test(phone);
}

function showNotification(message, type = 'error') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// تفعيل تأثيرات التمرير
function handleScroll() {
    const elements = document.querySelectorAll('.fade-in');
    elements.forEach(element => {
        const elementTop = element.getBoundingClientRect().top;
        const elementBottom = element.getBoundingClientRect().bottom;
        
        if (elementTop < window.innerHeight && elementBottom > 0) {
            element.classList.add('active');
        }
    });
}

// تحميل الصفحة وتفعيل التأثيرات
window.addEventListener('load', () => {
    loadFeaturedItems();
    handleScroll();
});

// تفعيل تأثيرات التمرير عند التمرير
window.addEventListener('scroll', handleScroll);

// تفعيل القائمة المتجاوبة
const navbar = document.querySelector('.navbar');
let lastScroll = 0;

window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset;
    
    if (currentScroll > lastScroll && currentScroll > 100) {
        navbar.style.transform = 'translateY(-100%)';
    } else {
        navbar.style.transform = 'translateY(0)';
    }
    
    if (currentScroll === 0) {
        navbar.style.boxShadow = '0 2px 15px rgba(0,0,0,0.1)';
    } else {
        navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.2)';
    }
    
    lastScroll = currentScroll;
});

// إدارة الحجوزات
let bookings = JSON.parse(localStorage.getItem('bookings')) || [];

// دالة إضافة حجز جديد
function addBooking(booking) {
    bookings.push({
        ...booking,
        totalPrice: booking.totalPrice.replace('ريال', 'دج') // تحديث العملة
    });
    localStorage.setItem('bookings', JSON.stringify(bookings));
    displayBookings();
    showNotification('تم إضافة الحجز بنجاح', 'success');
}

// دالة عرض الحجوزات
function displayBookings() {
    const bookingsList = document.getElementById('bookingsList');
    if (!bookingsList) return;

    bookingsList.innerHTML = '';
    
    if (bookings.length === 0) {
        bookingsList.innerHTML = '<div class="no-bookings">لا توجد حجوزات حالياً</div>';
        return;
    }

    bookings.forEach((booking, index) => {
        const bookingItem = document.createElement('div');
        bookingItem.className = 'booking-item';
        
        bookingItem.innerHTML = `
            <img src="${booking.productImage}" alt="${booking.productName}">
            <div class="booking-info">
                <h3>${booking.productName}</h3>
                <div class="booking-details">
                    <div class="booking-detail">
                        <i class="fas fa-calendar"></i>
                        <span>تاريخ الاستلام: ${booking.startDate}</span>
                    </div>
                    <div class="booking-detail">
                        <i class="fas fa-clock"></i>
                        <span>المدة: ${booking.duration} يوم</span>
                    </div>
                    <div class="booking-detail">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>السعر الإجمالي: ${booking.totalPrice}</span>
                    </div>
                    <div class="booking-status ${getStatusClass(booking.status)}">
                        ${getStatusText(booking.status)}
                    </div>
                </div>
            </div>
        `;
        
        bookingsList.appendChild(bookingItem);
    });
}

// دالة الحصول على تصنيف حالة الحجز
function getStatusClass(status) {
    switch(status) {
        case 'pending': return 'status-pending';
        case 'confirmed': return 'status-confirmed';
        case 'completed': return 'status-completed';
        default: return 'status-pending';
    }
}

// دالة الحصول على نص حالة الحجز
function getStatusText(status) {
    switch(status) {
        case 'pending': return 'قيد المراجعة';
        case 'confirmed': return 'تم التأكيد';
        case 'completed': return 'مكتمل';
        default: return 'قيد المراجعة';
    }
}

// فتح نافذة الحجز
function openRentModal(productCard) {
    const modal = document.getElementById('modalOverlay');
    const modalProductImage = document.getElementById('modalProductImage');
    const modalProductName = document.getElementById('modalProductName');
    const modalProductPrice = document.getElementById('modalProductPrice');
    
    const productImage = productCard.querySelector('img').src;
    const productName = productCard.querySelector('h3').textContent;
    const productPrice = productCard.querySelector('.price').textContent;
    
    modalProductImage.src = productImage;
    modalProductName.textContent = productName;
    modalProductPrice.textContent = productPrice;
    
    // إعادة تعيين النموذج
    document.getElementById('bookingForm').reset();
    
    // تعيين التاريخ الأدنى لليوم الحالي
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('rentDate').min = today;
    
    // تحديث السعر الإجمالي
    updateTotalPrice();
    
    // عرض النافذة
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

// إغلاق النافذة المنبثقة
function closeRentModal() {
    const modal = document.getElementById('modalOverlay');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

// تحديث السعر الإجمالي
function updateTotalPrice() {
    const duration = parseInt(document.getElementById('rentDuration').value) || 1;
    const priceText = document.getElementById('modalProductPrice').textContent;
    const dailyPrice = parseInt(priceText.match(/\d+/)[0]);
    const totalPrice = duration * dailyPrice;
    
    document.getElementById('totalPrice').textContent = `${totalPrice} دج`;
}

// التحقق من صحة رقم الهاتف الجزائري
function isValidAlgerianPhone(phone) {
    const phoneRegex = /^(05|06|07)[0-9]{8}$/;
    return phoneRegex.test(phone);
}

// إضافة مستمعي الأحداث عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // نموذج الحجز
    const bookingForm = document.getElementById('bookingForm');
    const rentDuration = document.getElementById('rentDuration');
    const modal = document.getElementById('modalOverlay');
    
    // تحديث السعر عند تغيير المدة
    if (rentDuration) {
        rentDuration.addEventListener('input', updateTotalPrice);
    }
    
    // معالجة تقديم نموذج الحجز
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const phoneNumber = document.getElementById('rentPhone').value;
            if (!isValidAlgerianPhone(phoneNumber)) {
                showNotification('الرجاء إدخال رقم هاتف جزائري صحيح (يبدأ ب 05 أو 06 أو 07 متبوعًا بـ 8 أرقام)');
                return;
            }
            
            const booking = {
                productName: document.getElementById('modalProductName').textContent,
                productImage: document.getElementById('modalProductImage').src,
                customerName: document.getElementById('rentName').value,
                phoneNumber: phoneNumber,
                startDate: document.getElementById('rentDate').value,
                duration: parseInt(document.getElementById('rentDuration').value),
                totalPrice: document.getElementById('totalPrice').textContent,
                status: 'pending',
                bookingDate: new Date().toISOString()
            };
            
            // حفظ الحجز
            addBooking(booking);
            
            // إظهار رسالة نجاح
            showNotification('تم تسجيل الحجز بنجاح! سنتصل بك قريباً');
            
            // إغلاق النافذة
            closeRentModal();
        });
    }
    
    // إغلاق النافذة عند النقر خارجها
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeRentModal();
            }
        });
    }
    
    // إضافة مستمعي الأحداث لأزرار الحجز
    document.querySelectorAll('.rent-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productCard = this.closest('.product-card');
            openRentModal(productCard);
        });
    });
});

// التحقق من صحة البريد الإلكتروني
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// التحقق من صحة كلمة المرور
function isValidPassword(password) {
    return password.length >= 8;
}

// فتح نافذة التسجيل
function openRegisterModal() {
    const modal = document.getElementById('registerModal');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

// إغلاق نافذة التسجيل
function closeRegisterModal() {
    const modal = document.getElementById('registerModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

// حفظ بيانات المستخدم
function saveUserData(userData) {
    let users = JSON.parse(localStorage.getItem('users') || '[]');
    users.push(userData);
    localStorage.setItem('users', JSON.stringify(users));
}

// التحقق من وجود البريد الإلكتروني
function isEmailExists(email) {
    const users = JSON.parse(localStorage.getItem('users') || '[]');
    return users.some(user => user.email === email);
}

// إضافة مستمعي الأحداث عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // زر التسجيل
    const registerBtn = document.getElementById('registerBtn');
    if (registerBtn) {
        registerBtn.addEventListener('click', openRegisterModal);
    }

    // نموذج التسجيل
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // التحقق من البريد الإلكتروني
            const email = document.getElementById('email').value;
            if (!isValidEmail(email)) {
                showNotification('الرجاء إدخال بريد إلكتروني صحيح');
                return;
            }

            // التحقق من وجود البريد الإلكتروني
            if (isEmailExists(email)) {
                showNotification('هذا البريد الإلكتروني مسجل بالفعل');
                return;
            }

            // التحقق من كلمة المرور
            const password = document.getElementById('password').value;
            if (!isValidPassword(password)) {
                showNotification('يجب أن تتكون كلمة المرور من 8 أحرف على الأقل');
                return;
            }

            // التحقق من تطابق كلمة المرور
            const confirmPassword = document.getElementById('confirmPassword').value;
            if (password !== confirmPassword) {
                showNotification('كلمتا المرور غير متطابقتين');
                return;
            }

            // التحقق من رقم الهاتف
            const phone = document.getElementById('phone').value;
            if (!isValidAlgerianPhone(phone)) {
                showNotification('الرجاء إدخال رقم هاتف جزائري صحيح');
                return;
            }

            // جمع بيانات المستخدم
            const userData = {
                fullName: document.getElementById('fullName').value,
                email: email,
                phone: phone,
                password: password, // في التطبيق الحقيقي، يجب تشفير كلمة المرور
                city: document.getElementById('city').value,
                registrationDate: new Date().toISOString()
            };

            // حفظ بيانات المستخدم
            saveUserData(userData);

            // إظهار رسالة نجاح
            showNotification('تم التسجيل بنجاح! يمكنك الآن تسجيل الدخول');

            // إغلاق النافذة وإعادة تعيين النموذج
            closeRegisterModal();
            registerForm.reset();
        });
    }

    // إغلاق النافذة عند النقر خارجها
    const registerModal = document.getElementById('registerModal');
    if (registerModal) {
        registerModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeRegisterModal();
            }
        });
    }
});

// عرض الإشعارات
function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // إظهار الإشعار
    setTimeout(() => notification.classList.add('show'), 100);
    
    // إخفاء وإزالة الإشعار
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// تأثيرات القائمة العلوية
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.querySelector('.navbar');
    const menuToggle = document.querySelector('.menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    const menuBackdrop = document.querySelector('.menu-backdrop');
    const navLinksItems = document.querySelectorAll('.nav-links a');

    // تأثير التمرير
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // تبديل القائمة للهواتف
    menuToggle.addEventListener('click', function() {
        menuToggle.classList.toggle('active');
        navLinks.classList.toggle('active');
        menuBackdrop.classList.toggle('active');
        document.body.style.overflow = navLinks.classList.contains('active') ? 'hidden' : '';
    });

    // إغلاق القائمة عند النقر على الخلفية
    menuBackdrop.addEventListener('click', function() {
        menuToggle.classList.remove('active');
        navLinks.classList.remove('active');
        menuBackdrop.classList.remove('active');
        document.body.style.overflow = '';
    });

    // إغلاق القائمة عند النقر على الروابط
    navLinksItems.forEach(link => {
        link.addEventListener('click', function() {
            menuToggle.classList.remove('active');
            navLinks.classList.remove('active');
            menuBackdrop.classList.remove('active');
            document.body.style.overflow = '';
        });
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const accountBtn = document.querySelector(".account-btn");
    const dropdown = document.querySelector(".dropdown");

    accountBtn.addEventListener("click", function (event) {
        event.stopPropagation();
        dropdown.classList.toggle("active");
    });

    document.addEventListener("click", function (event) {
        if (!dropdown.contains(event.target)) {
            dropdown.classList.remove("active");
        }
    });
});


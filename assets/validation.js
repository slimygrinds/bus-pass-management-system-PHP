/**
 * Client-Side Form Validation
 * 
 * Provides real-time validation for all form fields.
 * Works alongside Bootstrap validation classes.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

// ==========================================
// Validation Rules Object
// ==========================================
const ValidationRules = {
    
    /**
     * Validate name field (only letters and spaces)
     * @param {string} name - Name to validate
     * @returns {object} - {valid: bool, message: string}
     */
    validateName: function(name) {
        if (!name || name.trim() === '') {
            return { valid: false, message: 'This field is required.' };
        }
        if (name.trim().length < 2) {
            return { valid: false, message: 'Must be at least 2 characters.' };
        }
        if (name.trim().length > 100) {
            return { valid: false, message: 'Must not exceed 100 characters.' };
        }
        if (!/^[a-zA-Z\s]+$/.test(name.trim())) {
            return { valid: false, message: 'Only alphabets and spaces allowed.' };
        }
        return { valid: true, message: '' };
    },

    /**
     * Validate username (alphanumeric, dots, underscores)
     * @param {string} username - Username to validate
     * @returns {object}
     */
    validateUsername: function(username) {
        if (!username || username.trim() === '') {
            return { valid: false, message: 'Username is required.' };
        }
        if (username.length < 4) {
            return { valid: false, message: 'Username must be at least 4 characters.' };
        }
        if (username.length > 50) {
            return { valid: false, message: 'Username must not exceed 50 characters.' };
        }
        if (!/^[a-zA-Z0-9._]+$/.test(username)) {
            return { valid: false, message: 'Only letters, numbers, dots and underscores allowed.' };
        }
        return { valid: true, message: '' };
    },

    /**
     * Validate email address
     * @param {string} email - Email to validate
     * @returns {object}
     */
    validateEmail: function(email) {
        if (!email || email.trim() === '') {
            return { valid: false, message: 'Email is required.' };
        }
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            return { valid: false, message: 'Please enter a valid email address.' };
        }
        return { valid: true, message: '' };
    },

    /**
     * Validate password (minimum 8 characters)
     * @param {string} password - Password to validate
     * @returns {object}
     */
    validatePassword: function(password) {
        if (!password || password === '') {
            return { valid: false, message: 'Password is required.' };
        }

        if (password.length > 50) {
            return { valid: false, message: 'Password must not exceed 50 characters.' };
        }
        return { valid: true, message: '' };
    },

    /**
     * Validate mobile number (exactly 10 digits)
     * @param {string} mobile - Mobile number to validate
     * @returns {object}
     */
    validateMobile: function(mobile) {
        if (!mobile || mobile.trim() === '') {
            return { valid: false, message: 'Mobile number is required.' };
        }
        if (!/^\d+$/.test(mobile)) {
            return { valid: false, message: 'Only digits allowed.' };
        }
        if (mobile.length !== 10) {
            return { valid: false, message: 'Mobile number must be exactly 10 digits.' };
        }
        return { valid: true, message: '' };
    },

    /**
     * Validate pincode (exactly 6 digits)
     * @param {string} pincode - Pincode to validate
     * @returns {object}
     */
    validatePincode: function(pincode) {
        if (!pincode || pincode.trim() === '') {
            return { valid: false, message: 'Pincode is required.' };
        }
        if (!/^\d{6}$/.test(pincode)) {
            return { valid: false, message: 'Pincode must be exactly 6 digits.' };
        }
        return { valid: true, message: '' };
    },

    /**
     * Validate Aadhar number (exactly 12 digits)
     * @param {string} aadhar - Aadhar number to validate
     * @returns {object}
     */
    validateAadhar: function(aadhar) {
        if (!aadhar || aadhar.trim() === '') {
            return { valid: true };
        }
        if (!/^\d{12}$/.test(aadhar)) {
            return { valid: false, message: 'Aadhar number must be exactly 12 digits.' };
        }
        return { valid: true, message: '' };
    },

    /**
     * Validate age (1-100, numbers only)
     * @param {string} age - Age to validate
     * @returns {object}
     */
    validateAge: function(age) {
        if (!age || age.toString().trim() === '') {
            return { valid: false, message: 'Age is required.' };
        }
        const ageNum = parseInt(age);
        if (isNaN(ageNum) || ageNum < 1 || ageNum > 100) {
            return { valid: false, message: 'Please enter a valid age (1-100).' };
        }
        return { valid: true, message: '' };
    },

    /**
     * Validate date of birth
     * @param {string} dob - Date string to validate
     * @returns {object}
     */
    validateDOB: function(dob) {
        if (!dob || dob.trim() === '') {
            return { valid: false, message: 'Date of birth is required.' };
        }
        const dobDate = new Date(dob);
        const today = new Date();
        if (dobDate >= today) {
            return { valid: false, message: 'Date of birth cannot be in the future.' };
        }
        const age = Math.floor((today - dobDate) / (365.25 * 24 * 60 * 60 * 1000));
        if (age < 5 || age > 100) {
            return { valid: false, message: 'Please enter a valid date of birth.' };
        }
        return { valid: true, message: '' };
    },

    /**
     * Validate address (10-500 characters)
     * @param {string} address - Address to validate
     * @returns {object}
     */
    validateAddress: function(address) {
        if (!address || address.trim() === '') {
            return { valid: false, message: 'Address is required.' };
        }
        if (address.trim().length < 10) {
            return { valid: false, message: 'Address must be at least 10 characters.' };
        }
        if (address.trim().length > 500) {
            return { valid: false, message: 'Address must not exceed 500 characters.' };
        }
        return { valid: true, message: '' };
    },

    /**
     * Validate required select dropdown
     * @param {string} value - Selected value
     * @returns {object}
     */
    validateRequired: function(value) {
        if (!value || value.trim() === '') {
            return { valid: false, message: 'This field is required.' };
        }
        return { valid: true, message: '' };
    },

    /**
     * Validate confirm password matches
     * @param {string} password - Original password
     * @param {string} confirmPassword - Confirmation password
     * @returns {object}
     */
    validateConfirmPassword: function(password, confirmPassword) {
        if (!confirmPassword || confirmPassword === '') {
            return { valid: false, message: 'Please confirm your password.' };
        }
        if (password !== confirmPassword) {
            return { valid: false, message: 'Passwords do not match.' };
        }
        return { valid: true, message: '' };
    }
};

// ==========================================
// Helper: Show field validation state
// ==========================================

/**
 * Show validation feedback on a form field
 * @param {HTMLElement} field - The input element
 * @param {object} result - Validation result {valid, message}
 */
function showValidation(field, result) {
    // Remove existing feedback
    let parent = field.closest('.mb-3') || field.closest('.lp-field');
    if (!parent) {
        parent = field.parentElement.classList.contains('input-group') ? field.parentElement.parentElement : field.parentElement;
    }
    const existingFeedback = parent.querySelector('.invalid-feedback, .valid-feedback');
    
    if (result.valid) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        if (existingFeedback) existingFeedback.remove();
    } else {
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');
        if (existingFeedback) {
            existingFeedback.textContent = result.message;
            existingFeedback.className = 'invalid-feedback d-block';
        } else {
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback d-block'; // d-block needed if outside input-group
            feedback.textContent = result.message;
            
            if (field.parentElement.classList.contains('input-group')) {
                field.parentElement.insertAdjacentElement('afterend', feedback);
            } else {
                field.parentElement.appendChild(feedback);
            }
        }
    }
}

/**
 * Clear validation state from a field
 * @param {HTMLElement} field - The input element
 */
function clearValidation(field) {
    field.classList.remove('is-valid', 'is-invalid');
    let parent = field.closest('.mb-3') || field.closest('.lp-field');
    if (!parent) {
        parent = field.parentElement.classList.contains('input-group') ? field.parentElement.parentElement : field.parentElement;
    }
    const feedback = parent.querySelector('.invalid-feedback, .valid-feedback');
    if (feedback) feedback.remove();
}

// ==========================================
// Input Restriction Helpers
// ==========================================

/**
 * Restrict input to only digits
 * Usage: onkeypress="return allowOnlyDigits(event)"
 */
function allowOnlyDigits(event) {
    const charCode = event.which || event.keyCode;
    if (charCode < 48 || charCode > 57) {
        event.preventDefault();
        return false;
    }
    return true;
}

/**
 * Restrict input to only letters and spaces
 * Usage: onkeypress="return allowOnlyLetters(event)"
 */
function allowOnlyLetters(event) {
    const charCode = event.which || event.keyCode;
    if ((charCode >= 65 && charCode <= 90) || (charCode >= 97 && charCode <= 122) || charCode === 32) {
        return true;
    }
    event.preventDefault();
    return false;
}

/**
 * Limit input length
 * Usage: oninput="limitLength(this, 10)"
 */
function limitLength(element, maxLen) {
    if (element.value.length > maxLen) {
        element.value = element.value.slice(0, maxLen);
    }
}

// ==========================================
// Auto-calculate age from DOB
// ==========================================

/**
 * Calculate age from date of birth and set age field
 * @param {string} dob - Date of birth string
 * @param {string} ageFieldId - ID of the age input field
 */
function calculateAge(dob, ageFieldId) {
    if (!dob) return;
    const today = new Date();
    const birthDate = new Date(dob);
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    const ageField = document.getElementById(ageFieldId);
    if (ageField && age > 0 && age <= 100) {
        ageField.value = age;
    }
}

// ==========================================
// File Upload Preview
// ==========================================

/**
 * Preview uploaded image before submitting
 * @param {HTMLInputElement} input - File input element
 * @param {string} previewId - ID of the preview img element
 */
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;
    
    if (input.files && input.files[0]) {
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!allowedTypes.includes(input.files[0].type)) {
            alert('Please select a JPG or PNG image.');
            input.value = '';
            return;
        }
        
        // Validate file size (2MB)
        if (input.files[0].size > 2 * 1024 * 1024) {
            alert('File size must not exceed 2MB.');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            
            // Hide placeholder text if exists
            const placeholder = preview.parentElement.querySelector('.placeholder-text');
            if (placeholder) placeholder.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

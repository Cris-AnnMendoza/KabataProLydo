// ═══════════════════════════════════════════════════════════════
// REGISTRATION FORM HANDLER
// Version: 1.0.0 (Last updated: 2026-09-16)
// ═══════════════════════════════════════════════════════════════

console.log('LYDO Registration Script Loaded - Version 1.0.0');
console.log('Cache-bust: ' + new Date().toISOString());

document.addEventListener('DOMContentLoaded', function() {
  console.log('DOM Ready - Initializing registration...');
  
  const registerModal = document.getElementById('registerModal');
  const privacyModal = document.getElementById('privacyModal');
  const registerForm = document.getElementById('registerForm');
  const openRegisterBtn = document.getElementById('openRegisterModal');
  const navRegisterBtn = document.getElementById('navRegisterBtn');
  const mobileRegisterBtn = document.getElementById('mobileRegisterBtn');
  const heroRegisterBtn = document.getElementById('heroRegisterBtn');
  const ctaRegisterBtn = document.getElementById('ctaRegisterBtn');
  const closeRegisterBtn = document.getElementById('closeRegisterModal');
  const closePrivacyBtn = document.getElementById('closePrivacyModal');
  const openPrivacyBtn = document.getElementById('openPrivacyModal');
  const acceptPrivacyBtn = document.getElementById('acceptPrivacy');
  
  console.log('Modal elements:', {
    registerModal: !!registerModal,
    privacyModal: !!privacyModal,
    registerForm: !!registerForm,
    openRegisterBtn: !!openRegisterBtn
  });
  
  let currentStep = 1;
  const totalSteps = 5;

  // ─────────────────────────────────────────
  // LOAD ORGANIZATIONS
  // ─────────────────────────────────────────
  
  let organizationsLoaded = false;
  
  async function loadOrganizations() {
    if (organizationsLoaded) return; // Prevent duplicate loads
    
    try {
      console.log('Loading organizations...');
      
      // Load all active orgs for youth member dropdown
      const youthResponse = await fetch('get_organizations.php?type=all');
      const youthData = await youthResponse.json();
      console.log('Youth orgs response:', youthData);
      
      if (youthData.success && youthData.organizations) {
        const youthSelect = document.getElementById('r_org_name');
        if (youthSelect) {
          // Clear existing options except placeholder
          while (youthSelect.options.length > 1) {
            youthSelect.remove(1);
          }
          
          youthData.organizations.forEach(org => {
            const option = document.createElement('option');
            option.value = org.id;
            const status = org.accreditation_status === 'active' ? '' : ' (Pending)';
            option.textContent = org.name + status;
            youthSelect.appendChild(option);
          });
          console.log('Added', youthData.organizations.length, 'orgs to youth dropdown');
        }
      }
      
      // Load accredited orgs for president dropdown  
      const presResponse = await fetch('get_organizations.php?type=accredited');
      const presData = await presResponse.json();
      console.log('President orgs response:', presData);
      
      if (presData.success && presData.organizations) {
        const presSelect = document.getElementById('r_organization');
        if (presSelect) {
          // Clear existing options except placeholder
          while (presSelect.options.length > 1) {
            presSelect.remove(1);
          }
          
          presData.organizations.forEach(org => {
            const option = document.createElement('option');
            option.value = org.id;
            option.textContent = org.name + (org.adviser_name ? ` (Adviser: ${org.adviser_name})` : '');
            presSelect.appendChild(option);
          });
          console.log('Added', presData.organizations.length, 'accredited orgs to president dropdown');
        }
      }
      
      organizationsLoaded = true;
    } catch (error) {
      console.error('Error loading organizations:', error);
    }
  }
  
  // Load organizations when registration modal opens
  registerModal?.addEventListener('click', (e) => {
    if (e.target !== registerModal) return; // Only if clicking the modal itself
    loadOrganizations();
  });
  
  // Also load when opening via buttons
  openRegisterBtn?.addEventListener('click', () => {
    setTimeout(() => loadOrganizations(), 100);
  });
  navRegisterBtn?.addEventListener('click', () => {
    setTimeout(() => loadOrganizations(), 100);
  });
  mobileRegisterBtn?.addEventListener('click', () => {
    setTimeout(() => loadOrganizations(), 100);
  });
  heroRegisterBtn?.addEventListener('click', () => {
    setTimeout(() => loadOrganizations(), 100);
  });
  ctaRegisterBtn?.addEventListener('click', () => {
    setTimeout(() => loadOrganizations(), 100);
  });

  // ─────────────────────────────────────────
  // MODAL CONTROLS
  // ─────────────────────────────────────────
  
  function openRegisterModal() {
    registerModal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeRegisterModall() {
    registerModal.classList.remove('open');
    document.body.style.overflow = '';
  }
  
  openRegisterBtn?.addEventListener('click', openRegisterModal);
  navRegisterBtn?.addEventListener('click', openRegisterModal);
  mobileRegisterBtn?.addEventListener('click', openRegisterModal);
  heroRegisterBtn?.addEventListener('click', openRegisterModal);
  ctaRegisterBtn?.addEventListener('click', openRegisterModal);

  closeRegisterBtn?.addEventListener('click', closeRegisterModall);

  registerModal?.addEventListener('click', (e) => {
    if (e.target === registerModal) {
      closeRegisterModall();
    }
  });

  openPrivacyBtn?.addEventListener('click', (e) => {
    e.preventDefault();
    privacyModal.classList.add('open');
  });

  closePrivacyBtn?.addEventListener('click', () => {
    privacyModal.classList.remove('open');
  });

  acceptPrivacyBtn?.addEventListener('click', () => {
    document.getElementById('r_consent').checked = true;
    privacyModal.classList.remove('open');
  });

  privacyModal?.addEventListener('click', (e) => {
    if (e.target === privacyModal) {
      privacyModal.classList.remove('open');
    }
  });

  // ─────────────────────────────────────────
  // FORM NAVIGATION
  // ─────────────────────────────────────────

  const nextBtn = document.getElementById('nextStep');
  const prevBtn = document.getElementById('prevStep');
  const submitBtn = document.getElementById('submitRegister');
  const stepCounter = document.getElementById('stepCounter');

  function updateStepDisplay() {
    // Hide all steps
    document.querySelectorAll('.form-step').forEach(step => {
      step.classList.remove('active');
    });
    
    // Show current step
    document.getElementById(`step-${currentStep}`).classList.add('active');
    
    // Update step indicator
    document.querySelectorAll('.step').forEach((step, idx) => {
      const stepNum = idx + 1;
      step.classList.remove('active', 'done');
      
      if (stepNum < currentStep) {
        step.classList.add('done');
      } else if (stepNum === currentStep) {
        step.classList.add('active');
      }
    });
    
    // Update buttons
    prevBtn.style.visibility = currentStep === 1 ? 'hidden' : 'visible';
    nextBtn.style.display = currentStep === totalSteps ? 'none' : 'block';
    submitBtn.style.display = currentStep === totalSteps ? 'block' : 'none';
    
    // Update counter
    stepCounter.textContent = `Step ${currentStep} of ${totalSteps}`;
  }

  nextBtn?.addEventListener('click', () => {
    if (validateStep(currentStep)) {
      if (currentStep < totalSteps) {
        currentStep++;
        updateStepDisplay();
      }
    }
  });

  prevBtn?.addEventListener('click', () => {
    if (currentStep > 1) {
      currentStep--;
      updateStepDisplay();
    }
  });

  // ─────────────────────────────────────────
  // FORM VALIDATION - STEP 4 ORGANIZATION
  // ─────────────────────────────────────────
  
  // Validate organization selection before moving to next step
  function validateOrganizationStep() {
    const orgSelect = document.getElementById('r_org_name');
    const newOrgName = document.getElementById('r_new_org_name').value.trim();
    
    // Must have either selected an org OR entered a new org name
    if (!orgSelect.value && !newOrgName) {
      showNotification('Error', 'Please select an organization or create a new one.');
      return false;
    }
    
    // If creating new org, category is required
    if (newOrgName && !document.getElementById('r_new_org_category').value) {
      showNotification('Error', 'Please select a category for the new organization.');
      return false;
    }
    
    return true;
  }

  // Add organization validation to next button
  const originalNextClick = nextBtn.onclick;
  nextBtn?.addEventListener('click', function(e) {
    // Step 4 has special validation for organization
    if (currentStep === 3) { // Step 3 is org info step (0-indexed would be different, but form shows step 4)
      if (!validateOrganizationStep()) {
        e.preventDefault();
        return;
      }
    }
  });

  // ─────────────────────────────────────────
  // FORM VALIDATION
  // ─────────────────────────────────────────

  function validateStep(step) {
    const requiredFields = getRequiredFieldsForStep(step);
    let isValid = true;

    for (const field of requiredFields) {
      const input = document.getElementById(field);
      if (!input) continue;

      if (input.type === 'checkbox' && input.dataset.requiresCheck) {
        if (!input.checked) {
          showNotification('Error', `${field} is required.`);
          isValid = false;
          break;
        }
      } else if (input.type === 'email') {
        if (!validateEmail(input.value)) {
          showNotification('Error', 'Please enter a valid email address.');
          isValid = false;
          break;
        }
      } else if (input.tagName === 'SELECT') {
        if (!input.value) {
          showNotification('Error', `Please select ${input.parentElement.querySelector('label').textContent}`);
          isValid = false;
          break;
        }
      } else {
        if (!input.value?.trim()) {
          showNotification('Error', `${input.placeholder || field} is required.`);
          isValid = false;
          break;
        }
      }
    }

    return isValid;
  }

  function getRequiredFieldsForStep(step) {
    switch(step) {
      case 1:
        return ['r_fname', 'r_lname', 'r_gender', 'r_bdate', 'r_age', 'r_civil', 'r_contact', 'r_email', 'r_password', 'r_confirm_pw'];
      case 2:
        return ['r_street', 'r_barangay'];
      case 3:
        return [];
      case 4:
        return ['r_educ_status', 'r_employment'];
      case 5:
        return ['r_consent'];
      default:
        return [];
    }
  }

  function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  }

  // ─────────────────────────────────────────
  // FORM SUBMISSION
  // ─────────────────────────────────────────

  registerForm?.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (!validateStep(totalSteps)) {
      return;
    }

    const formData = new FormData();
    
    // Personal Info
    formData.append('first_name', document.getElementById('r_fname').value);
    formData.append('middle_name', document.getElementById('r_mname').value || '');
    formData.append('last_name', document.getElementById('r_lname').value);
    formData.append('suffix', document.getElementById('r_suffix').value || '');
    formData.append('gender', document.getElementById('r_gender').value);
    formData.append('birthdate', document.getElementById('r_bdate').value);
    formData.append('age', document.getElementById('r_age').value);
    formData.append('civil_status', document.getElementById('r_civil').value);
    formData.append('contact_number', document.getElementById('r_contact').value);
    formData.append('email', document.getElementById('r_email').value);
    formData.append('password', document.getElementById('r_password').value);
    formData.append('confirm_password', document.getElementById('r_confirm_pw').value);
    
    // Address Info
    formData.append('house_number', document.getElementById('r_street').value);
    formData.append('barangay', document.getElementById('r_barangay').value);
    formData.append('municipality', document.getElementById('r_municipality').value);
    formData.append('province', document.getElementById('r_province').value);
    formData.append('zip_code', document.getElementById('r_zip').value);
    
    // Classification
    const classifications = Array.from(document.querySelectorAll('input[name="classification"]:checked'))
      .map(cb => cb.value);
    if (classifications.length > 0) {
      formData.append('youth_classification', JSON.stringify(classifications));
    }
    
    // Education
    formData.append('educational_status', document.getElementById('r_educ_status').value);
    formData.append('school_name', document.getElementById('r_school').value || '');
    formData.append('course_or_grade', document.getElementById('r_course').value || '');
    formData.append('employment_status', document.getElementById('r_employment').value);
    
    // Organization
    const orgSelect = document.getElementById('r_org_name').value;
    const newOrgName = document.getElementById('r_new_org_name').value.trim();
    const newOrgCategory = document.getElementById('r_new_org_category').value;
    
    // Send organization info based on what was selected/created
    if (orgSelect) {
      // User selected existing organization
      formData.append('organization_id', orgSelect);
    } else if (newOrgName) {
      // User creating new organization
      formData.append('new_organization_name', newOrgName);
      formData.append('new_organization_category', newOrgCategory);
    }
    
    formData.append('organization_role', document.getElementById('r_org_position').value || '');
    formData.append('years_membership', document.getElementById('r_org_years').value || '0');
    
    // Additional
    formData.append('skills', document.getElementById('r_skills').value || '');
    formData.append('interests', document.getElementById('r_interests').value || '');
    
    const programs = Array.from(document.querySelectorAll('input[name="programs"]:checked'))
      .map(cb => cb.value);
    if (programs.length > 0) {
      formData.append('programs_interested', JSON.stringify(programs));
    }
    
    formData.append('volunteer_availability', document.getElementById('r_volunteer').value || '');
    
    // Registration type
    formData.append('register_as', document.querySelector('input[name="register_as"]:checked').value);

    try {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';

      const response = await fetch('register.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        showNotification('Success', result.message);
        registerForm.reset();
        currentStep = 1;
        updateStepDisplay();
        setTimeout(() => {
          registerModal.classList.remove('open');
          document.body.style.overflow = '';
        }, 2000);
      } else {
        showNotification('Error', result.message);
      }
    } catch (error) {
      showNotification('Error', 'An error occurred. Please try again.');
      console.error(error);
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="fas fa-check"></i> Register Account';
    }
  });

  // ─────────────────────────────────────────
  // AUTO-CALCULATE AGE FROM BIRTHDATE
  // ─────────────────────────────────────────

  document.getElementById('r_bdate')?.addEventListener('change', (e) => {
    const birthdate = new Date(e.target.value);
    const today = new Date();
    let age = today.getFullYear() - birthdate.getFullYear();
    const monthDiff = today.getMonth() - birthdate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthdate.getDate())) {
      age--;
    }
    
    document.getElementById('r_age').value = age;
  });

  // ─────────────────────────────────────────
  // PASSWORD STRENGTH INDICATOR
  // ─────────────────────────────────────────

  const passwordInput = document.getElementById('r_password');
  const confirmPwInput = document.getElementById('r_confirm_pw');

  passwordInput?.addEventListener('input', updatePasswordStrength);
  confirmPwInput?.addEventListener('input', checkPasswordMatch);

  function updatePasswordStrength() {
    const password = passwordInput.value;
    const strengthContainer = document.getElementById('password-strength-container');
    const strengthValue = document.getElementById('password-strength-value');
    const strengthLabel = document.getElementById('password-strength-label');

    if (!password) {
      strengthContainer.style.display = 'none';
      return;
    }

    strengthContainer.style.display = 'block';

    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;

    const strengthFill = document.getElementById('password-strength-fill');
    const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
    const colors = ['#c62828', '#e65100', '#f57f17', '#fbc02d', '#43a047', '#2e7d32'];

    strengthFill.style.width = (strength * 20) + '%';
    strengthFill.style.background = colors[Math.max(0, strength - 1)];
    strengthValue.textContent = labels[strength] || 'Very Weak';
    strengthValue.className = 'strength-' + labels[strength].toLowerCase().replace(' ', '-');

    checkPasswordMatch();
  }

  function checkPasswordMatch() {
    const matchHint = document.getElementById('password-match-hint');
    const password = passwordInput.value;
    const confirmPw = confirmPwInput.value;

    if (confirmPw && password !== confirmPw) {
      matchHint.textContent = '✗ Passwords do not match';
      matchHint.style.color = '#c62828';
      matchHint.style.display = 'block';
    } else if (confirmPw && password === confirmPw) {
      matchHint.textContent = '✓ Passwords match';
      matchHint.style.color = '#2e7d32';
      matchHint.style.display = 'block';
    } else {
      matchHint.style.display = 'none';
    }
  }

  // ─────────────────────────────────────────
  // TOGGLE PASSWORD VISIBILITY
  // ─────────────────────────────────────────

  document.querySelectorAll('.toggle-pw').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const input = btn.previousElementSibling;
      const icon = btn.querySelector('i');
      
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      } else {
        input.type = 'password';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      }
    });
  });

  // ─────────────────────────────────────────
  // REGISTER AS CHANGE HANDLER
  // ─────────────────────────────────────────

  document.querySelectorAll('input[name="register_as"]').forEach(radio => {
    radio.addEventListener('change', (e) => {
      const orgSection = document.getElementById('organization-section');
      const youthOrgSection = document.getElementById('youth-org-section');
      
      if (e.target.value === 'organization_president') {
        orgSection.style.display = 'block';
        youthOrgSection.style.display = 'none';
      } else {
        orgSection.style.display = 'none';
        youthOrgSection.style.display = 'block';
      }
    });
  });

  // Youth organization affiliation toggle
  document.querySelectorAll('input[name="org_affiliation"]').forEach(radio => {
    radio.addEventListener('change', (e) => {
      const youthOrgSelect = document.getElementById('youth-org-select');
      youthOrgSelect.style.display = e.target.value === 'affiliated' ? 'block' : 'none';
    });
  });

  // ─────────────────────────────────────────
  // NOTIFICATION
  // ─────────────────────────────────────────

  function showNotification(title, message) {
    const overlay = document.getElementById('notificationOverlay');
    const titleEl = document.getElementById('notificationTitle');
    const messageEl = document.getElementById('notificationMessage');
    const iconEl = document.getElementById('notificationIcon');

    titleEl.textContent = title;
    messageEl.textContent = message;

    if (title === 'Success') {
      iconEl.innerHTML = '<i class="fas fa-check-circle" style="color: #2e7d32;"></i>';
    } else {
      iconEl.innerHTML = '<i class="fas fa-exclamation-circle" style="color: #c62828;"></i>';
    }

    overlay.classList.add('show');
  }

  window.closeNotification = function() {
    const overlay = document.getElementById('notificationOverlay');
    overlay.classList.remove('show');
  };

  // Initialize first step display
  updateStepDisplay();
});

// ═══════════════════════════════════════════════════════════════
// NAVBAR SCROLL EFFECT
// ═══════════════════════════════════════════════════════════════

window.addEventListener('scroll', () => {
  const navbar = document.getElementById('navbar');
  if (window.scrollY > 50) {
    navbar.classList.add('scrolled');
  } else {
    navbar.classList.remove('scrolled');
  }
});

// Mobile menu toggle
const hamburger = document.getElementById('hamburger');
const mobileMenu = document.getElementById('mobileMenu');

hamburger?.addEventListener('click', () => {
  hamburger.classList.toggle('open');
  mobileMenu.classList.toggle('open');
});

// Close mobile menu on link click
document.querySelectorAll('.mobile-link').forEach(link => {
  link.addEventListener('click', () => {
    hamburger.classList.remove('open');
    mobileMenu.classList.remove('open');
  });
});

// ═════════════════════════════════════════════════════════════════
// CONTACT FORM
// ═════════════════════════════════════════════════════════════════

const contactForm = document.getElementById('contactForm');
contactForm?.addEventListener('submit', async (e) => {
  e.preventDefault();

  const formData = new FormData(contactForm);

  try {
    const response = await fetch('submit_contact.php', {
      method: 'POST',
      body: formData
    });

    const result = await response.json();

    if (result.success) {
      const overlay = document.getElementById('notificationOverlay');
      const titleEl = document.getElementById('notificationTitle');
      const messageEl = document.getElementById('notificationMessage');
      const iconEl = document.getElementById('notificationIcon');

      titleEl.textContent = 'Success';
      messageEl.textContent = 'Your message has been sent successfully!';
      iconEl.innerHTML = '<i class="fas fa-check-circle" style="color: #2e7d32;"></i>';
      overlay.classList.add('show');

      contactForm.reset();
    } else {
      const overlay = document.getElementById('notificationOverlay');
      const titleEl = document.getElementById('notificationTitle');
      const messageEl = document.getElementById('notificationMessage');
      titleEl.textContent = 'Error';
      messageEl.textContent = result.message || 'Failed to send message.';
      overlay.classList.add('show');
    }
  } catch (error) {
    console.error('Error:', error);
    const overlay = document.getElementById('notificationOverlay');
    const titleEl = document.getElementById('notificationTitle');
    const messageEl = document.getElementById('notificationMessage');
    titleEl.textContent = 'Error';
    messageEl.textContent = 'An error occurred. Please try again.';
    overlay.classList.add('show');
  }
});

// ═════════════════════════════════════════════════════════════════
// SCROLL ANIMATIONS
// ═════════════════════════════════════════════════════════════════

const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.1 });

document.querySelectorAll('.animate-on-scroll').forEach(el => {
  observer.observe(el);
});

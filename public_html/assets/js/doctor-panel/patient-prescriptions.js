document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const patient_id = urlParams.get('patient_id') || 1;
    fetchPatientPrescriptions(patient_id);
});

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    })[character]);
}

function fetchPatientPrescriptions(patient_id) {
    fetch(`../../api/doctor-panel/patient-prescriptions.php?patient_id=${encodeURIComponent(patient_id)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updatePatientPrescriptionsUI(data);
            } else {
                console.error('Error fetching prescriptions:', data.error);
            }
        })
        .catch(error => console.error('Fetch error:', error));
}

function updatePatientPrescriptionsUI(data) {
    // 1. Patient Header
    const pt = data.patient;
    const dob = new Date(pt.dob);
    const ageDifMs = Date.now() - dob.getTime();
    const ageDate = new Date(ageDifMs);
    const age = Math.abs(ageDate.getUTCFullYear() - 1970);
    
    document.querySelector('.patient-details h2').textContent = pt.full_name;
    document.querySelector('.patient-avatar-wrap img').src = pt.photo_url || 'https://i.pravatar.cc/120?img=32';
    document.querySelector('.mrn-badge').textContent = `MRN-${pt.patient_id}`;
    
    const metaRow = document.querySelector('.patient-meta-row');
    metaRow.textContent = `${age} y/o ${pt.gender} • DOB: ${pt.dob} • Blood: ${pt.blood_group || 'Unknown'}`;

    // 2. Allergies
    const allergyBox = document.querySelector('.allergy-warning-box');
    if (data.allergies && data.allergies.length > 0) {
        allergyBox.style.display = 'flex';
        allergyBox.querySelector('.warning-text p').textContent = data.allergies
            .map(allergy => `${allergy.allergy_name} (${allergy.severity || 'severity not recorded'})`)
            .join(', ');
    } else {
        allergyBox.style.display = 'none';
    }

    // 3. Prescriptions List
    const rxContainer = document.querySelector('.rx-records-stack');
    if (rxContainer) {
        rxContainer.replaceChildren();
        if (!data.prescriptions.length) {
            const emptyMessage = document.createElement('p');
            emptyMessage.className = 'rx-card';
            emptyMessage.textContent = 'No prescriptions have been recorded for this patient.';
            rxContainer.appendChild(emptyMessage);
            return;
        }

        data.prescriptions.forEach(rx => {
            const rxDate = new Date(rx.created_at).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            const medicationName = rx.medication_name
                ? `${rx.medication_name}${rx.dose_strength ? ` ${rx.dose_strength}` : ''}`
                : rx.title;
            const instructions = rx.sig_instructions || rx.route_frequency || 'No medication instructions recorded.';
            const cardHTML = `
            <div class="rx-card">
              <div class="rx-card-top flex-between">
                <div class="rx-main-info flex-row">
                  <div class="rx-icon green-icon-bg"><i class="fa-solid fa-capsules"></i></div>
                  <div>
                    <div class="rx-title-group">
                      <h3 class="rx-title">${escapeHtml(medicationName)}</h3>
                      <span class="status-tag green-tag">${escapeHtml(rx.status.toUpperCase())}</span>
                    </div>
                    <p class="rx-sig">
                      <strong>Sig:</strong> ${escapeHtml(instructions)}
                    </p>
                  </div>
                </div>
  
                <div class="rx-actions-vertical">
                  <button class="btn-link"><i class="fa-solid fa-sliders"></i> Adjust Dose</button>
                  <button class="btn-link"><i class="fa-solid fa-rotate-right"></i> Request Refill</button>
                </div>
              </div>
  
              <div class="rx-card-bottom grid-4-col">
                <div>
                  <span class="col-label">COURSE</span>
                  <strong class="col-value">${rxDate} - Present</strong>
                </div>
                <div>
                  <span class="col-label">PRESCRIBER</span>
                  <strong class="col-value">${escapeHtml(rx.prescriber_name)}</strong>
                </div>
                <div>
                  <span class="col-label">DISPENSER</span>
                  <strong class="col-value">NHMRD Pharmacy</strong>
                </div>
                <div>
                  <span class="col-label">${rx.medication_name ? 'REFILLS LEFT' : 'PRESCRIPTION'}</span>
                  <strong class="col-value green-text">${rx.medication_name ? `${escapeHtml(rx.refills ?? 0)} refills remaining` : escapeHtml(rx.title)}</strong>
                </div>
              </div>
            </div>
            `;
            rxContainer.insertAdjacentHTML('beforeend', cardHTML);
        });
    }
}

window.handleLogout = function () {
  if (confirm('Are you sure you want to log out of the NHMRD Provider Portal?')) {
    fetch('../../api/logout.php')
      .then(res => res.json())
      .then(data => {
        window.location.href = data.redirect || '../../../index.php';
      })
      .catch(() => {
        window.location.href = '../../../index.php';
      });
  }
};

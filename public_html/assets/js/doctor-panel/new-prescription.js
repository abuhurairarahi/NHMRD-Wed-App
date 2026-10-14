    document.addEventListener("DOMContentLoaded", function() {
        function escapeHtml(value) {
            return String(value).replace(/[&<>"']/g, character => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            })[character]);
        }

        // --- Utility to update Encounter Summary (Meds & Labs) ---
        function updateSummary() {
            // Medications Summary Update
            const medSection = document.querySelectorAll('.form-section-card')[2]; 
            if (medSection) {
                const medCards = medSection.querySelectorAll('.med-order-card');
                let medNames = [];
                medCards.forEach(card => {
                    const nameEl = card.querySelector('.med-name');
                    if(nameEl) medNames.push(nameEl.innerText.split('(')[0].trim());
                });

                const medStatBox = document.querySelectorAll('.stat-box')[0];
                if(medStatBox) {
                    medStatBox.querySelector('.st-val').innerText = `${medCards.length} Active`;
                    medStatBox.querySelector('.st-sub').innerText = medNames.length > 0 ? medNames.join(', ') : 'None';
                }
            }

            // Labs Summary Update
            const labSection = document.querySelectorAll('.form-section-card')[3];
            if (labSection) {
                const labCards = labSection.querySelectorAll('.lab-order-card');
                let labNames = [];
                labCards.forEach(card => {
                    const nameEl = card.querySelector('.lab-name');
                    if(nameEl) labNames.push(nameEl.innerText.split('(')[0].trim());
                });
                
                const labStatBox = document.querySelectorAll('.stat-box')[1];
                if(labStatBox) {
                    labStatBox.querySelector('.st-val').innerText = `${labCards.length} Ordered`;
                    labStatBox.querySelector('.st-sub').innerText = labNames.length > 0 ? labNames.join(', ') : 'None';
                }
            }
        }

        // --- Section 2: Clinical Acuity Selection & DB Save ---
        const acuityOptions = document.querySelectorAll('.acuity-option');
        const summaryAcuityVal = document.querySelector('.acc-val');
        const consultationBanner = document.querySelector('.consultation-banner');

        // Mock Database Save Function
        function saveAcuityToDatabase(acuityLevel) {
            console.log(`[DB SAVE] Acuity Level updated to: ${acuityLevel}`);
            
            // Example AJAX/Fetch call to backend
            /*
            fetch('/api/encounter/update-acuity', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': 'your-csrf-token-here'
                },
                body: JSON.stringify({
                    encounter_id: '#ENC-449102-09',
                    acuity_level: acuityLevel,
                    timestamp: new Date().toISOString()
                })
            })
            .then(response => response.json())
            .then(data => console.log('Successfully saved to DB:', data))
            .catch(error => console.error('Error saving to DB:', error));
            */
        }

        if (acuityOptions.length > 0) {
            acuityOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // 1. Remove active classes from all options
                    acuityOptions.forEach(opt => {
                        opt.classList.remove('active-acuity', 'danger-acuity', 'purple-acuity');
                    });

                    // 2. Identify the selected option and apply the appropriate active class
                    const titleText = this.querySelector('.acuity-title').innerText.trim();
                    const iconHtml = this.querySelector('i').outerHTML;
                    
                    let activeClass = 'active-acuity'; // Default fallback
                    let summaryColorClass = 'text-amber';

                    if (titleText === 'Primary') {
                        activeClass = 'active-acuity';
                        summaryColorClass = 'text-sky';
                    } else if (titleText === 'Moderate') {
                        activeClass = 'active-acuity';
                        summaryColorClass = 'text-amber';
                    } else if (titleText === 'Critical') {
                        activeClass = 'danger-acuity';
                        summaryColorClass = 'text-red';
                    } else if (titleText === 'Surgical Needed') {
                        activeClass = 'purple-acuity';
                        summaryColorClass = 'text-purple';
                    }

                    this.classList.add(activeClass);

                    // 3. Update the Encounter Summary sidebar
                    if (summaryAcuityVal) {
                        summaryAcuityVal.className = `acc-val ${summaryColorClass}`;
                        summaryAcuityVal.innerHTML = `${iconHtml} ${titleText} Condition`;
                    }

                    // Show/Hide surgical consultation banner based on selection
                    if (consultationBanner) {
                        consultationBanner.style.display = (titleText === 'Surgical Needed') ? 'block' : 'none';
                    }

                    // 4. Trigger the Database Save Event
                    saveAcuityToDatabase(titleText);
                });
            });
        }

        // --- Section 3: Prescribed Medications ---
        const medSection = document.querySelectorAll('.form-section-card')[2];
        if (medSection) {
            const btnAddMed = medSection.querySelector('.btn-add-primary');
            const medDraftBox = medSection.querySelector('.draft-med-box');
            
            if (btnAddMed && medDraftBox) {
                btnAddMed.addEventListener('click', (e) => {
                    e.preventDefault();
                    medDraftBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    medDraftBox.querySelector('input').focus();
                });

                const btnConfirmAddMed = medDraftBox.querySelector('.btn-confirm-add');
                const btnCancelMed = medDraftBox.querySelector('.btn-cancel');

                btnConfirmAddMed.addEventListener('click', (e) => {
                    e.preventDefault();
                    const inputs = medDraftBox.querySelectorAll('input');
                    const medName = inputs[0].value.trim();
                    if (!medName) {
                        alert('Enter a medication name before adding it.');
                        inputs[0].focus();
                        return;
                    }
                    const medDose = inputs[1].value.trim();
                    const medRoute = inputs[2].value.trim();
                    const medDispense = inputs[3].value.trim();
                    const medInstructions = inputs[4].value.trim();

                    const newCard = document.createElement('div');
                    newCard.className = 'med-order-card';
                    newCard.innerHTML = `
                        <div class="med-card-header">
                          <div class="med-title-row">
                            <span class="med-badge-type bg-indigo">N</span>
                            <h4 class="med-name">${escapeHtml(medName)}</h4>
                            <span class="med-tag tag-blue">New Order</span>
                          </div>
                          <div class="med-actions">
                            <button class="icon-action"><i class="fa-solid fa-pen"></i></button>
                            <button class="icon-action danger delete-btn"><i class="fa-regular fa-trash-can"></i></button>
                          </div>
                        </div>
                        <div class="med-ndc">Pending NDC</div>
                        <div class="med-grid-4col">
                          <div class="med-field"><span class="m-lbl">DOSE & STRENGTH</span><span class="m-val">${escapeHtml(medDose)}</span></div>
                          <div class="med-field"><span class="m-lbl">ROUTE & FREQUENCY</span><span class="m-val">${escapeHtml(medRoute)}</span></div>
                          <div class="med-field"><span class="m-lbl">DISPENSE / REFILLS</span><span class="m-val">${escapeHtml(medDispense)}</span></div>
                          <div class="med-field"><span class="m-lbl">SAFETY NOTE</span><span class="m-val text-amber">Review required</span></div>
                        </div>
                        <div class="med-instructions-box">
                          <i class="fa-solid fa-circle-info"></i>
                          <span><strong>SIG / INSTRUCTIONS:</strong> ${escapeHtml(medInstructions)}</span>
                        </div>
                    `;
                    
                    medSection.insertBefore(newCard, medDraftBox);
                    
                    newCard.querySelector('.delete-btn').addEventListener('click', function(e) {
                        e.preventDefault();
                        newCard.remove();
                        updateSummary();
                    });

                    inputs.forEach(input => input.value = '');
                    updateSummary();
                });

                btnCancelMed.addEventListener('click', (e) => {
                    e.preventDefault();
                    medDraftBox.querySelectorAll('input').forEach(input => input.value = '');
                });
            }
        }

        // --- Section 4: Assign Medical & Laboratory Tests ---
        const labSection = document.querySelectorAll('.form-section-card')[3];
        if (labSection) {
            const btnAddTest = labSection.querySelector('.btn-add-primary');
            const labDraftBox = labSection.querySelector('.draft-med-box');
            
            if (btnAddTest && labDraftBox) {
                btnAddTest.addEventListener('click', (e) => {
                    e.preventDefault();
                    labDraftBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    labDraftBox.querySelector('input').focus();
                });

                const btnConfirmAddTest = labDraftBox.querySelector('.btn-confirm-add');
                const btnCancelTest = labDraftBox.querySelector('.btn-cancel');

                btnConfirmAddTest.addEventListener('click', (e) => {
                    e.preventDefault();
                    const inputs = labDraftBox.querySelectorAll('input');
                    const testName = inputs[0].value.trim();
                    if (!testName) {
                        alert('Enter a test name before adding it.');
                        inputs[0].focus();
                        return;
                    }
                    const testCode = inputs[1].value.trim();
                    const testRoute = inputs[2].value.trim();
                    const testDispense = inputs[3].value.trim();
                    const testInstructions = inputs[4].value.trim();

                    const newCard = document.createElement('div');
                    newCard.className = 'lab-order-card';
                    newCard.innerHTML = `
                        <div class="lab-card-top">
                          <div class="lab-title-row">
                            <span class="lab-icon-box bg-blue"><i class="fa-solid fa-microscope"></i></span>
                            <div>
                              <h4 class="lab-name">${escapeHtml(testName)}</h4>
                              <span class="loinc-tag">${escapeHtml(testCode)}</span>
                              <span class="lab-status-badge badge-amber">New Order</span>
                            </div>
                          </div>
                          <div class="med-actions">
                            <button class="icon-action danger delete-btn"><i class="fa-regular fa-trash-can"></i></button>
                          </div>
                        </div>
                        <p class="lab-desc">Details: ${escapeHtml(testRoute)} &bull; ${escapeHtml(testDispense)} &bull; ${escapeHtml(testInstructions)}</p>
                        <div class="lab-card-footer">
                          <span>Priority: <strong>Routine</strong></span>
                        </div>
                    `;
                    
                    labSection.insertBefore(newCard, labDraftBox);
                    
                    newCard.querySelector('.delete-btn').addEventListener('click', function(e) {
                        e.preventDefault();
                        newCard.remove();
                        updateSummary();
                    });

                    inputs.forEach(input => input.value = '');
                    updateSummary();
                });

                btnCancelTest.addEventListener('click', (e) => {
                    e.preventDefault();
                    labDraftBox.querySelectorAll('input').forEach(input => input.value = '');
                });
            }
        }

        // --- Global Deletes for pre-rendered items ---
        document.querySelectorAll('.med-actions .danger').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const card = e.target.closest('.med-order-card, .lab-order-card');
                if (card) {
                    card.remove();
                    updateSummary();
                }
            });
        });
        
        // --- Global Action Buttons ---
        const btnSaveIssue = document.querySelector('.btn-save-issue');
        if(btnSaveIssue) {
            btnSaveIssue.addEventListener('click', e => {
                e.preventDefault();
                const urlParams = new URLSearchParams(window.location.search);
                const patientId = urlParams.get('patient_id');
                const titleInput = document.querySelector('#prescription-title');
                const title = titleInput ? titleInput.value.trim() : '';
                if (!patientId || !title) {
                    alert('A patient and prescription title are required.');
                    return;
                }
                const symptoms = document.querySelector('#prescription-symptoms')?.value.trim() || '';
                const chiefComplaint = document.querySelector('#chief-complaint')?.value.trim() || '';
                const currentCondition = document.querySelector('.acuity-option.active-acuity, .acuity-option.danger-acuity, .acuity-option.purple-acuity')
                    ?.querySelector('.acuity-title')?.innerText.trim() || '';
                const doctorsStatement = document.querySelector('#doctors-statement')?.value.trim() || '';
                const nextVisitDate = document.querySelector('#next-visit-date')?.value || '';
                const visitType = document.querySelector('#visit-type')?.value || 'in_person';

                const medCards = document.querySelectorAll('.med-order-card');
                let medications = [];
                medCards.forEach(card => {
                    const name = card.querySelector('.med-name') ? card.querySelector('.med-name').innerText.trim() : '';
                    const vals = card.querySelectorAll('.m-val');
                    const strength = vals[0] ? vals[0].innerText.trim() : '';
                    const route = vals[1] ? vals[1].innerText.trim() : '';
                    const dispenseAndRefills = vals[2] ? vals[2].innerText.trim() : '';
                    const refillsMatch = dispenseAndRefills.match(/(\d+)\s*refills?/i);
                    const quantity = dispenseAndRefills.replace(/\s*[•·]\s*\d+\s*refills?.*$/i, '').trim();
                    const instrSpan = card.querySelector('.med-instructions-box span');
                    const instructions = instrSpan ? instrSpan.innerText.replace('SIG / INSTRUCTIONS:', '').trim() : '';
                    if (name) {
                        medications.push({ name, strength, route, quantity, refills: refillsMatch ? Number(refillsMatch[1]) : 0, instructions });
                    }
                });

                const labTests = Array.from(document.querySelectorAll('.lab-order-card')).map(card => ({
                    name: card.querySelector('.lab-name')?.innerText.trim() || '',
                    code: card.querySelector('.loinc-tag')?.innerText.trim() || '',
                    instructions: card.querySelector('.lab-desc')?.innerText.trim() || ''
                })).filter(test => test.name);

                const formData = new FormData();
                formData.append('patient_id', patientId);
                formData.append('title', title);
                formData.append('chief_complaint', chiefComplaint);
                formData.append('symptoms', symptoms);
                formData.append('current_condition', currentCondition);
                formData.append('doctors_statement', doctorsStatement);
                formData.append('next_visit_date', nextVisitDate);
                formData.append('visit_type', visitType);
                formData.append('medications', JSON.stringify(medications));
                formData.append('lab_tests', JSON.stringify(labTests));

                btnSaveIssue.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
                btnSaveIssue.disabled = true;

                fetch('../../api/doctor-panel/create-prescription.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Prescription successfully saved to database!');
                        window.location.href = `patient-prescription-records.php?patient_id=${encodeURIComponent(patientId)}`;
                    } else {
                        alert('Failed to save prescription: ' + (data.error || 'Unknown error'));
                        btnSaveIssue.innerHTML = '<i class="fa-solid fa-circle-check"></i> Save &amp; Issue Prescription';
                        btnSaveIssue.disabled = false;
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Server error occurred.');
                    btnSaveIssue.innerHTML = '<i class="fa-solid fa-circle-check"></i> Save &amp; Issue Prescription';
                    btnSaveIssue.disabled = false;
                });
            });
        }
        
        const btnPrintOrder = document.querySelector('.btn-print-order');
        if(btnPrintOrder) btnPrintOrder.addEventListener('click', e => { e.preventDefault(); window.print(); });

        // Initialize Summary on load
        updateSummary();
        
        // Set initial banner state based on default Acuity
        if (consultationBanner) consultationBanner.style.display = 'none'; // Defaulting to hidden unless "Surgical Needed" is active
    });

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

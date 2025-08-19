function initAdminJobs() {
    console.log("Admin Jobs script loaded");

    const table = document.getElementById("tcat-jobs-table");
    if (!table) { console.warn("Jobs table not found"); return; }

    const tbody = table.querySelector("tbody");
    const rows = Array.from(tbody.querySelectorAll("tr"));

    const searchInput  = document.getElementById("filter-search");
    const typeFilter   = document.getElementById("filter-job-type");
    const schoolFilter = document.getElementById("filter-school");
    const statusFilter = document.getElementById("filter-status");
    const paginationContainer = document.getElementById("tcat-pagination");

    const previewPanel = document.getElementById("tcat-job-preview-panel");
    const previewContent = document.getElementById("job-preview-content");
    const closePreviewBtn = document.getElementById("close-job-preview");

    const rowsPerPage = 10;
    let filteredRows = [...rows];
    let currentPage = 1;

    function makeBtn(label, onClick, { active=false, disabled=false } = {}) {
        const btn = document.createElement("button");
        btn.textContent = label;
        btn.style.margin = "0 4px";
        btn.style.padding = "6px 10px";
        btn.style.border = "1px solid #ccc";
        btn.style.borderRadius = "4px";
        btn.style.cursor = disabled ? "not-allowed" : "pointer";
        btn.style.background = active ? "#003848" : "#f1f1f1";
        btn.style.color = active ? "#fff" : "#000";
        btn.disabled = disabled;
        if (!disabled) btn.addEventListener("click", onClick);
        return btn;
    }

    function renderPagination() {
        if (!paginationContainer) return;

        paginationContainer.innerHTML = "";
        const total = filteredRows.length;
        const pageCount = Math.ceil(total / rowsPerPage);
        if (pageCount <= 1) return;

        paginationContainer.appendChild(makeBtn("Previous", () => displayPage(currentPage - 1), { disabled: currentPage === 1 }));
        for (let i = 1; i <= pageCount; i++) {
            paginationContainer.appendChild(makeBtn(String(i), () => displayPage(i), { active: i === currentPage }));
        }
        paginationContainer.appendChild(makeBtn("Next", () => displayPage(currentPage + 1), { disabled: currentPage === pageCount }));
    }

    function displayPage(page) {
        currentPage = page;
        rows.forEach(r => r.style.display = "none");

        if (filteredRows.length === 0) {
            if (paginationContainer) {
                paginationContainer.innerHTML = '<span style="font-size:14px;color:#666;">No matching jobs.</span>';
            }
            return;
        }

        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        filteredRows.forEach((row, idx) => {
            row.style.display = (idx >= start && idx < end) ? "" : "none";
        });

        renderPagination();
    }

    function filterJobs() {
    const safe = v => (v || "").toLowerCase().trim();
    const searchValue = safe(searchInput && searchInput.value);
    const typeValue   = safe(typeFilter && typeFilter.value);
    const schoolValue = safe(schoolFilter && schoolFilter.value);
    const statusValue = safe(statusFilter && statusFilter.value);

    filteredRows = rows.filter(row => {
        const titleCell = row.querySelector("td:first-child");
        const rowTitle  = safe(titleCell && titleCell.textContent);
        
        // Split pipe-separated job types into array
        const rowType = (row.getAttribute("data-job-type") || "")
                        .split('|')
                        .map(t => t.toLowerCase().trim());
        const rowSchool = safe(row.getAttribute("data-school"));
        const rowStatus = safe(row.getAttribute("data-status"));

        // Type match: if type filter is empty, true; else check exact match in rowType
        const typeMatch = !typeValue || rowType.includes(typeValue);

        const searchMatch = !searchValue || rowTitle.includes(searchValue);
        const schoolMatch = !schoolValue || rowSchool.includes(schoolValue);
        const statusMatch = !statusValue || rowStatus.includes(statusValue);

        return typeMatch && searchMatch && schoolMatch && statusMatch;
    });

    currentPage = 1;
    displayPage(1);
}




    // =========================
    // Job Preview with Styled Specification Table
    // =========================
    tbody.addEventListener("click", function(e) {
        const btn = e.target.closest(".preview-job-btn");
        if (!btn) return;

        const jobId = btn.getAttribute("data-job-id");
        if (!jobId) return;

        table.style.display = "none";
        if (paginationContainer) paginationContainer.style.display = "none";
        previewPanel.style.display = "block";
        previewContent.innerHTML = "<p>Loading job details...</p>";

        fetch(tcat_ajax_obj.ajax_url, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                action: "get_job_details",
                job_id: jobId,
                nonce: tcat_ajax_obj.nonce
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const job = data.data;
                let html = `
                    <div style="
                        background: #fff; 
                        border-radius: 10px; 
                        padding: 20px; 
                        box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
                        color: #000; 
                        font-family: Arial, sans-serif;
                    ">
                        <h2 style="margin:0;">${job.title}</h2>
                        <p><strong>Location:</strong> ${job.location || 'N/A'}</p>
                        <p><strong>Closing Date:</strong> <span style="background:#ecc082; padding:2px 6px; border-radius:4px;">${job.closing_date || 'N/A'}</span></p>

                        <div style="margin:15px 0;">
                            <button id="show-overview" class="tab-btn" style="
                                padding:8px 16px; margin-right:10px; border:1px solid #003848; border-radius:5px; background:#fff; color:#000; cursor:pointer;
                                transition: all 0.3s;
                            ">Job Advert</button>
                            <button id="show-description" class="tab-btn" style="
                                padding:8px 16px; border:1px solid #003848; border-radius:5px; background:#fff; color:#000; cursor:pointer;
                                transition: all 0.3s;
                            ">Job Description</button>
                        </div>

                        <div id="job-content-container">
                            <div id="overview-content">
                                <h3>Job Specification</h3>
                                <hr>
                                <table style="width:100%; border-collapse: collapse; color: #000; margin-bottom:15px;">
                                    <tr>
                                        <td style="padding:6px; border-bottom:1px solid #eee;"><strong>Salary:</strong></td>
                                        <td style="padding:6px; border-bottom:1px solid #eee;">${job.salary || 'N/A'}</td>
                                        <td style="padding:6px; border-bottom:1px solid #eee;"><strong>Contractual Hours:</strong></td>
                                        <td style="padding:6px; border-bottom:1px solid #eee;">${job.contract_hours || 'N/A'}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:6px; border-bottom:1px solid #eee;"><strong>Job Type:</strong></td>
                                        <td style="padding:6px; border-bottom:1px solid #eee;">${job.job_type || 'N/A'}</td>
                                        <td style="padding:6px; border-bottom:1px solid #eee;"><strong>Job Category:</strong></td>
                                        <td style="padding:6px; border-bottom:1px solid #eee;">${job.job_category || 'N/A'}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:6px; border-bottom:1px solid #eee;"><strong>Date Posted:</strong></td>
                                        <td style="padding:6px; border-bottom:1px solid #eee;">${job.date_posted || 'N/A'}</td>
                                        <td style="padding:6px; border-bottom:1px solid #eee;"><strong>Attachments:</strong></td>
                                        <td style="padding:6px; border-bottom:1px solid #eee;">${job.attachment ? `<a href="${job.attachment}" target="_blank" download style="color:#000; text-decoration:underline;">Download</a>` : 'N/A'}</td>
                                    </tr>
                                </table>

                                <h3>Job Overview</h3>
                                <p style="line-height:1.6;">${job.overview || 'No overview available.'}</p>
                            </div>

                            <div id="description-content" style="display:none;">
                                <h3>Job Description</h3>
                                <hr>
                                <p style="line-height:1.6;">${job.job_description || 'No description provided.'}</p>
                            </div>
                        </div>
                    </div>
                `;
                previewContent.innerHTML = html;

                // Tab switching with hover effect
                const overviewBtn = document.getElementById("show-overview");
                const descriptionBtn = document.getElementById("show-description");

                [overviewBtn, descriptionBtn].forEach(btn => {
                    btn.addEventListener("mouseover", () => {
                        btn.style.background = "#003848";
                        btn.style.color = "#fff";
                    });
                    btn.addEventListener("mouseout", () => {
                        btn.style.background = "#fff";
                        btn.style.color = "#000";
                    });
                });


                overviewBtn.addEventListener("click", () => {
                    document.getElementById("overview-content").style.display = "block";
                    document.getElementById("description-content").style.display = "none";
                });
                descriptionBtn.addEventListener("click", () => {
                    document.getElementById("overview-content").style.display = "none";
                    document.getElementById("description-content").style.display = "block";
                });

            } else {
                previewContent.innerHTML = "<p>Failed to load job details.</p>";
            }
        })
        .catch(() => {
            previewContent.innerHTML = "<p>Error loading job details.</p>";
        });
    });

    // Close Preview (single button)
    if (closePreviewBtn) {
        closePreviewBtn.addEventListener("click", () => {
            previewPanel.style.display = "none";
            table.style.display = "";
            if (paginationContainer) paginationContainer.style.display = "";
            previewContent.innerHTML = "";
        });
    }

    [searchInput, typeFilter, schoolFilter, statusFilter].forEach(input => {
        if (input) {
            input.addEventListener("input", filterJobs);
            input.addEventListener("change", filterJobs);
        }
    });

    filterJobs();
}

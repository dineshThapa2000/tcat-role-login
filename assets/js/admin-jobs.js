function initAdminJobs() {
    console.log("Admin Jobs script loaded");

    const table = document.getElementById("tcat-jobs-table");
    if (!table) return;

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

    function makeBtn(label, onClick, options={}) {
        const btn = document.createElement("button");
        btn.textContent = label;
        btn.style.margin = "0 4px";
        btn.style.padding = "6px 10px";
        btn.style.border = "1px solid #ccc";
        btn.style.borderRadius = "4px";
        btn.style.cursor = options.disabled ? "not-allowed" : "pointer";
        btn.style.background = options.active ? "#003848" : "#f1f1f1";
        btn.style.color = options.active ? "#fff" : "#000";
        btn.disabled = options.disabled || false;
        if (!btn.disabled) btn.addEventListener("click", onClick);
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
            if (paginationContainer) paginationContainer.innerHTML = '<span style="font-size:14px;color:#666;">No matching jobs.</span>';
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

            const rowType = (row.getAttribute("data-job-type") || "").split('|').map(t => t.toLowerCase().trim());
            const rowSchool = safe(row.getAttribute("data-school"));
            const rowStatus = safe(row.getAttribute("data-status"));

            const typeMatch   = !typeValue || rowType.includes(typeValue);
            const searchMatch = !searchValue || rowTitle.includes(searchValue);
            const schoolMatch = !schoolValue || rowSchool.includes(schoolValue);
            const statusMatch = !statusValue || rowStatus.includes(statusValue);

            return typeMatch && searchMatch && schoolMatch && statusMatch;
        });

        currentPage = 1;
        displayPage(1);
    }

    // Job preview panel
    tbody.addEventListener("click", function(e) {
        const btn = e.target.closest(".preview-job-btn");
        if (!btn) return;

        const jobId = btn.getAttribute("data-job-id");
        if (!jobId) return;

        table.style.display = "none";
        if (paginationContainer) paginationContainer.style.display = "none";
        previewPanel.style.display = "block";
        previewContent.innerHTML = "<p>Loading job details...</p>";

        fetch(tcat_dashboard.ajaxurl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                action: "get_job_details",
                job_id: jobId,
                nonce: tcat_dashboard.nonce
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const job = data.data;
                let html = `
                    <h2>${job.title}</h2>
                    <p><strong>Location:</strong> ${job.location || 'N/A'}</p>
                    <p><strong>Closing Date:</strong><span class='closing-date'> ${job.closing_date || 'N/A'}</span></p>
                    <div>
                        <button id="show-overview">Job Advert</button>
                        <button id="show-description">Job Description</button>
                    </div>
                    <div id="job-content-container">
                        <div id="overview-content">
                        <h2> Job Overview</h2><hr>

                            <table>
                                <tr>
                                    <td>Salary:</td><td>${job.salary || 'N/A'}</td>
                                    <td>Hours:</td><td>${job.contract_hours || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td>Type:</td><td>${job.job_type || 'N/A'}</td>
                                    <td>Category:</td><td>${job.job_category || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td>Date Posted:</td><td>${job.date_posted || 'N/A'}</td>
                                    <td>Attachment:</td>
                                    <td>
                                        ${job.attachment 
                                            ? `<a href="${job.attachment}" target="_blank">Download</a>` 
                                            : 'N/A'}
                                    </td>
                                </tr>
                        
                            </table>
                            <p>${job.overview || 'No overview available.'}</p>
                        </div>
                        <div id="description-content" style="display:none;">
                        <h2> Job Description</h2><hr>
                            <p>${job.job_description || 'No description provided.'}</p>
                        </div>
                    </div>
                `;
                previewContent.innerHTML = html;

                document.getElementById("show-overview").addEventListener("click", () => {
                    document.getElementById("overview-content").style.display = "block";
                    document.getElementById("description-content").style.display = "none";
                });
                document.getElementById("show-description").addEventListener("click", () => {
                    document.getElementById("overview-content").style.display = "none";
                    document.getElementById("description-content").style.display = "block";
                });
            } else {
                previewContent.innerHTML = "<p>Failed to load job details.</p>";
            }
        })
        .catch(() => previewContent.innerHTML = "<p>Error loading job details.</p>");
    });

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

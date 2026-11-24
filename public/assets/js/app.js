(function () {
    "use strict";

    function ready(callback) {
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", callback);
        } else {
            callback();
        }
    }

    function initSidebar() {
        var toggle = document.getElementById("sidebarToggle");
        var sidebar = document.querySelector(".sidebar");
        var overlay = document.getElementById("sidebarOverlay");
        if (!toggle || !sidebar) {
            return;
        }

        function setOpen(open) {
            document.body.classList.toggle("sidebar-open", open);
            toggle.setAttribute("aria-expanded", open ? "true" : "false");
        }

        toggle.addEventListener("click", function () {
            setOpen(!document.body.classList.contains("sidebar-open"));
        });

        if (overlay) {
            overlay.addEventListener("click", function () {
                setOpen(false);
            });
        }

        sidebar.addEventListener("click", function (event) {
            if (event.target.closest("a")) {
                setOpen(false);
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                setOpen(false);
            }
        });

        window.addEventListener("resize", function () {
            if (window.innerWidth >= 992) {
                setOpen(false);
            }
        });
    }

    function initAlerts() {
        var alerts = document.querySelectorAll(".auto-hide-alert");
        alerts.forEach(function (alert) {
            window.setTimeout(function () {
                if (!alert.isConnected) {
                    return;
                }
                alert.classList.remove("show");
                window.setTimeout(function () {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 300);
            }, 6000);
        });
    }

    function initTooltips() {
        if (typeof bootstrap === "undefined" || !bootstrap.Tooltip) {
            return;
        }
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
            new bootstrap.Tooltip(element);
        });
    }

    function onlyDigits(value) {
        return (value || "").replace(/\D/g, "");
    }

    function maskCpf(value) {
        var digits = onlyDigits(value).slice(0, 11);
        return digits
            .replace(/(\d{3})(\d)/, "$1.$2")
            .replace(/(\d{3})(\d)/, "$1.$2")
            .replace(/(\d{3})(\d{1,2})$/, "$1-$2");
    }

    function maskPhone(value) {
        var digits = onlyDigits(value).slice(0, 11);
        if (digits.length <= 10) {
            return digits
                .replace(/(\d{2})(\d)/, "($1) $2")
                .replace(/(\d{4})(\d)/, "$1-$2");
        }
        return digits
            .replace(/(\d{2})(\d)/, "($1) $2")
            .replace(/(\d{5})(\d)/, "$1-$2");
    }

    function initMasks() {
        var inputs = document.querySelectorAll("input[data-mask]");
        inputs.forEach(function (input) {
            var type = (input.getAttribute("data-mask") || "").toLowerCase();
            function apply() {
                if (type === "cpf") {
                    input.value = maskCpf(input.value);
                } else if (type === "telefone" || type === "phone" || type === "celular") {
                    input.value = maskPhone(input.value);
                }
            }
            input.addEventListener("input", apply);
            input.addEventListener("blur", apply);
            if (input.value) {
                apply();
            }
        });
    }

    function activeLink() {
        var params = new URLSearchParams(window.location.search);
        var route = params.get("r") || "dashboard.index";
        var links = document.querySelectorAll(".sidebar-nav .sidebar-link:not([data-bs-toggle]), .portal-nav .portal-nav-link");
        var found = document.querySelector('.sidebar-nav .sidebar-link[aria-current="page"], .portal-nav .portal-nav-link[aria-current="page"]');
        if (found) {
            return;
        }

        var prefix = route.split(".")[0];
        links.forEach(function (link) {
            try {
                var linkRoute = new URL(link.href, window.location.origin).searchParams.get("r") || "";
                if (linkRoute === route || (route !== "dashboard.index" && linkRoute.indexOf(prefix + ".") === 0)) {
                    link.classList.add("active");
                    link.setAttribute("aria-current", "page");
                }
            } catch (error) {
                return;
            }
        });
    }

    function chartValues(values) {
        if (Array.isArray(values)) {
            return values.map(function (value) {
                return Number(value) || 0;
            });
        }
        return [];
    }

    function initCharts() {
        if (!window.PetCareCharts || typeof Chart === "undefined") {
            return;
        }

        Chart.defaults.font.family = "'Poppins', 'Inter', sans-serif";
        Chart.defaults.color = "#6c757d";

        var teal = "#20b8b0";
        var green = "#2f9e61";
        var blue = "#dff3f8";
        var softTeal = "rgba(32, 184, 176, 0.18)";

        var consultas = document.getElementById("chartConsultas");
        if (consultas && window.PetCareCharts.consultas_mes && (window.PetCareCharts.consultas_mes.labels || []).length) {
            new Chart(consultas.getContext("2d"), {
                type: "bar",
                data: {
                    labels: window.PetCareCharts.consultas_mes.labels,
                    datasets: [{
                        label: "Consultas",
                        data: chartValues(window.PetCareCharts.consultas_mes.data),
                        backgroundColor: teal,
                        hoverBackgroundColor: green,
                        borderRadius: 8,
                        maxBarThickness: 42
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: blue } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        var financeiro = document.getElementById("chartFinanceiro");
        if (financeiro && window.PetCareCharts.financeiro && (window.PetCareCharts.financeiro.labels || []).length) {
            new Chart(financeiro.getContext("2d"), {
                type: "bar",
                data: {
                    labels: window.PetCareCharts.financeiro.labels,
                    datasets: [
                        {
                            label: "Receitas",
                            data: chartValues(window.PetCareCharts.financeiro.receitas),
                            backgroundColor: green,
                            borderRadius: 8,
                            maxBarThickness: 28
                        },
                        {
                            label: "Despesas",
                            data: chartValues(window.PetCareCharts.financeiro.despesas),
                            backgroundColor: teal,
                            borderRadius: 8,
                            maxBarThickness: 28
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: "bottom" } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: blue } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        var clientes = document.getElementById("chartClientes");
        if (clientes && window.PetCareCharts.clientes_mes && (window.PetCareCharts.clientes_mes.labels || []).length) {
            new Chart(clientes.getContext("2d"), {
                type: "line",
                data: {
                    labels: window.PetCareCharts.clientes_mes.labels,
                    datasets: [{
                        label: "Novos clientes",
                        data: chartValues(window.PetCareCharts.clientes_mes.data),
                        borderColor: teal,
                        backgroundColor: softTeal,
                        pointBackgroundColor: green,
                        pointBorderColor: "#ffffff",
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        tension: 0.35,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: blue } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    }

    ready(function () {
        initSidebar();
        initAlerts();
        initTooltips();
        initMasks();
        activeLink();
        initCharts();
    });
})();

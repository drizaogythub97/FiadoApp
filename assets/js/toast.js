document.addEventListener("DOMContentLoaded", function(){

    window.showToast = function(message, type = "success") {

        let container = document.getElementById("toast-container");

        if (!container) {
            container = document.createElement("div");
            container.id = "toast-container";
            document.body.appendChild(container);
        }

        const toast = document.createElement("div");
        toast.classList.add("toast");

        if (type === "success") {
            toast.classList.add("toast-success");
            toast.innerHTML = `
                <span class="toast-icon">✔</span>
                <span>${message}</span>
            `;
        } else {
            toast.classList.add("toast-error");
            toast.innerHTML = `
                <span class="toast-icon">✖</span>
                <span>${message}</span>
            `;
        }

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = "0";
            toast.style.transition = "0.3s";
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    }

});
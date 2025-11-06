// Esperar a que el DOM esté completamente cargado
document.addEventListener("DOMContentLoaded", () => {
  // Verificar si el usuario ya está autenticado
  const authToken = localStorage.getItem("authToken") ||
    sessionStorage.getItem("authToken");

  // Si hay un token, redirigir al usuario según su rol
  console.log("authToken:", authToken);

  if (authToken) {
    document.addEventListener("DOMContentLoaded", () => {
      try {
        const userDataRaw = localStorage.getItem("userData") ||
          sessionStorage.getItem("userData");
        const userData = userDataRaw ? JSON.parse(userDataRaw) : null;

        console.log("Token detectado:", authToken);
        console.log("Datos de usuario:", userData);

        const userRole = userData?.id_rol ||
          userData?.role ||
          userData?.rol?.id ||
          null;

        const urlParams = new URLSearchParams(window.location.search);
        const redirectUrl = urlParams.get("redirect");

        console.log("Rol detectado:", userRole);

        if (redirectUrl) {
          console.log("Redirigiendo a:", redirectUrl);
          window.location.href = redirectUrl;
          return;
        }

        if (Number(userRole) === 1) {
          console.log("Redirigiendo al panel de administrador...");
          window.location.href = "/admin/dashboardAdmin";
        } else {
          console.log("Redirigiendo al panel de usuario...");
          window.location.href = "/usuario/home";
        }
      } catch (error) {
        console.error("Error leyendo los datos del usuario:", error);
        localStorage.removeItem("authToken");
        localStorage.removeItem("userData");
        sessionStorage.removeItem("authToken");
        sessionStorage.removeItem("userData");
      }
    });
  }


  console.log("Mostrando formulario de login");

  const form = document.querySelector("form#loginForm");
  const messageDiv = document.getElementById("responseMessage");

  if (!form) {
    console.error('No se encontró el formulario con id "loginForm"');
    return;
  }

  const submitButton = document.querySelector("#submitButton");
  const originalButtonHTML = submitButton?.innerHTML || "";

  function setLoading(isLoading) {
    if (!submitButton) return;
    if (isLoading) {
      submitButton.disabled = true;
      submitButton.setAttribute("aria-busy", "true");
      submitButton.innerHTML = `
          <svg xmlns="http://www.w3.org/2000/svg" class="rg-spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; margin-right:6px;">
            <circle cx="12" cy="12" r="10" stroke-opacity="0.2"></circle>
            <path d="M22 12a10 10 0 0 0-10-10" />
          </svg>
          <span>Cargando...</span>
        `;
    } else {
      submitButton.disabled = false;
      submitButton.removeAttribute("aria-busy");
      submitButton.innerHTML = originalButtonHTML;
    }
  }

  form.addEventListener("submit", async (event) => {
    event.preventDefault();

    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    if (!data.correo_electronico || !data.contrasena) {
      messageDiv.textContent = "Por favor completa todos los campos";
      messageDiv.style.color = "red";
      return;
    }

    const rememberMe = formData.has("remember");
    setLoading(true);

    try {
      const response = await fetch("http://127.0.0.1:8000/api/login", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify(data),
      });

      const result = await response.json();

      if (!response.ok) {
        let errorMessage = "Error al iniciar sesión: ";
        if (typeof result === "object") {
          const messages = Object.values(result).flat();
          errorMessage += messages.join(" | ");
        }
        messageDiv.textContent = errorMessage;
        messageDiv.style.color = "red";
      } else {
        const storage = rememberMe ? localStorage : sessionStorage;
        storage.setItem("authToken", result.token);
        storage.setItem("userData", JSON.stringify(result.user));

        messageDiv.textContent = "¡Inicio exitoso! Serás redirigido.";
        messageDiv.style.color = "green";

        const userRole = result.user?.id_rol || result.user?.role || result.role;
        const urlParams = new URLSearchParams(window.location.search);
        const redirectUrl = urlParams.get("redirect");

        setTimeout(() => {
          if (redirectUrl && userRole !== 1) {
            window.location.href = redirectUrl;
          } else if (userRole === 1) {
            window.location.href = "/admin/dashboardAdmin";
          } else {
            window.location.href = "/usuario/home";
          }
        }, 1500);
      }
    } catch (error) {
      console.error("Error de conexión:", error);
      messageDiv.textContent = "No se pudo conectar con el servidor.";
      messageDiv.style.color = "red";
    } finally {
      setLoading(false);
    }
  });

  const clearButton = document.createElement("button");
  clearButton.type = "button";
  clearButton.textContent = "Usar otra cuenta";
  clearButton.className = "text-sm text-blue-600 hover:underline mt-2";
  clearButton.addEventListener("click", function () {
    form.reset();
    localStorage.removeItem("authToken");
    sessionStorage.removeItem("authToken");
    messageDiv.textContent =
      "Campos limpiados. Puedes ingresar nuevas credenciales.";
    messageDiv.style.color = "blue";
  });

  form.parentNode.insertBefore(clearButton, form.nextSibling);
});

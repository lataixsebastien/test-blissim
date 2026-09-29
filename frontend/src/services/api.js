const baseUrl = (import.meta.env.VITE_API_BASE_URL ?? "/api/v1").replace(
  /\/$/,
  "",
);

export class ApiError extends Error {
  constructor(message, status, details = {}) {
    super(message);
    this.status = status;
    this.details = details;
  }
}

async function request(path, options = {}) {
  const controller = new AbortController();
  const cancel = () => controller.abort();
  options.signal?.addEventListener("abort", cancel, { once: true });
  if (options.signal?.aborted) controller.abort();
  const timer = window.setTimeout(cancel, 15000);
  try {
    const response = await fetch(`${baseUrl}${path}`, {
      ...options,
      signal: controller.signal,
      headers: {
        Accept: "application/json",
        ...(options.body ? { "Content-Type": "application/json" } : {}),
      },
    });
    if (response.status === 204) return;
    const body = await response.json().catch(() => null);
    if (!response.ok) {
      throw new ApiError(
        body?.error?.message ?? "Le serveur est indisponible. Réessayez.",
        response.status,
        body?.error?.details ?? {},
      );
    }
    if (!body || !Object.hasOwn(body, "data"))
      throw new ApiError(
        "La réponse du serveur est invalide.",
        response.status,
      );
    return body.data;
  } catch (error) {
    if (error instanceof ApiError) throw error;
    if (options.signal?.aborted) throw error;
    throw new ApiError(
      controller.signal.aborted
        ? "Le serveur met trop de temps à répondre. Réessayez."
        : "La connexion au serveur a échoué. Vérifiez votre connexion et réessayez.",
      0,
    );
  } finally {
    window.clearTimeout(timer);
    options.signal?.removeEventListener("abort", cancel);
  }
}

export const api = {
  products: (signal) => request("/products", { signal }),
  product: (id, signal) => request(`/products/${id}`, { signal }),
  comments: (id, signal) => request(`/products/${id}/comments`, { signal }),
  createComment: (input) =>
    request("/comments", {
      method: "POST",
      body: JSON.stringify(input),
    }),
  updateComment: (id, input) =>
    request(`/comments/${id}`, {
      method: "PUT",
      body: JSON.stringify(input),
    }),
  deleteComment: (id) => request(`/comments/${id}`, { method: "DELETE" }),
};

export const errorMessage = (error) =>
  error instanceof Error
    ? error.message
    : "Une erreur est survenue. Réessayez.";
export const formatPrice = (price) =>
  new Intl.NumberFormat("fr-FR", { style: "currency", currency: "USD" }).format(
    price,
  );

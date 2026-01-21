import { ref } from 'vue';

interface ApiResponse<T = any> {
    data: T;
    message?: string;
    errors?: Record<string, string[]>;
}

interface ApiOptions {
    method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
    body?: any;
    headers?: Record<string, string>;
}

export function useApi() {
    const loading = ref(false);
    const error = ref<string | null>(null);

    const getCSRFToken = (): string | null => {
        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : null;
    };

    const request = async <T = any>(url: string, options: ApiOptions = {}): Promise<T | null> => {
        loading.value = true;
        error.value = null;

        try {
            const headers: Record<string, string> = {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers,
            };

            // Agregar CSRF token para peticiones que modifican datos
            const csrfToken = getCSRFToken();
            if (csrfToken && options.method && options.method !== 'GET') {
                headers['X-XSRF-TOKEN'] = csrfToken;
            }

            const response = await fetch(url, {
                method: options.method || 'GET',
                headers,
                credentials: 'include', // Importante para enviar cookies
                body: options.body ? JSON.stringify(options.body) : undefined,
            });

            if (!response.ok) {
                if (response.status === 401) {
                    error.value = 'No autorizado. Por favor, inicia sesión.';
                    // Redirigir al login si es necesario
                    // window.location.href = '/login';
                } else if (response.status === 403) {
                    error.value = 'No tienes permisos para realizar esta acción.';
                } else if (response.status === 422) {
                    const data = await response.json();
                    error.value = data.message || 'Error de validación';
                } else {
                    error.value = `Error: ${response.statusText}`;
                }
                return null;
            }

            const data = await response.json();
            return data.data ?? data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Error de conexión';
            return null;
        } finally {
            loading.value = false;
        }
    };

    const get = <T = any>(url: string) => request<T>(url, { method: 'GET' });
    const post = <T = any>(url: string, body: any) => request<T>(url, { method: 'POST', body });
    const put = <T = any>(url: string, body: any) => request<T>(url, { method: 'PUT', body });
    const patch = <T = any>(url: string, body: any) => request<T>(url, { method: 'PATCH', body });
    const del = <T = any>(url: string) => request<T>(url, { method: 'DELETE' });

    return {
        loading,
        error,
        request,
        get,
        post,
        put,
        patch,
        del,
    };
}

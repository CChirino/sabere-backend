import { ref } from 'vue';
import { useApi } from './useApi';

export function usePushNotifications() {
    const { request, post } = useApi();
    const isSupported = ref(typeof Notification !== 'undefined' && 'serviceWorker' in navigator && 'PushManager' in window);
    const permission = ref(Notification?.permission ?? 'default');

    const requestPermission = async (): Promise<void> => {
        if (! isSupported.value) {
            return;
        }

        const result = await Notification.requestPermission();
        permission.value = result;
    };

    const subscribe = async (): Promise<void> => {
        if (! isSupported.value || permission.value !== 'granted') {
            await requestPermission();
        }

        if (permission.value !== 'granted') {
            return;
        }

        const vapidPublicKey = (window as any).VAPID_PUBLIC_KEY;
        if (! vapidPublicKey) {
            console.warn('VAPID_PUBLIC_KEY no está configurado.');

            return;
        }

        const registration = await navigator.serviceWorker.ready;
        const existing = await registration.pushManager.getSubscription();

        if (existing) {
            await saveSubscription(existing);

            return;
        }

        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey) as any,
        });

        await saveSubscription(subscription);
    };

    const unsubscribe = async (): Promise<void> => {
        const registration = await navigator.serviceWorker.ready;
        const existing = await registration.pushManager.getSubscription();

        if (existing) {
            const endpoint = existing.endpoint;
            await existing.unsubscribe();
            await request('/api/v1/push-subscriptions', {
                method: 'DELETE',
                body: { endpoint },
            });
        }
    };

    const saveSubscription = async (subscription: PushSubscription): Promise<void> => {
        const json = subscription.toJSON() as any;
        await post('/api/v1/push-subscriptions', {
            endpoint: json.endpoint,
            keys: json.keys,
        });
    };

    const urlBase64ToUint8Array = (base64String: string): Uint8Array => {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; i++) {
            outputArray[i] = rawData.charCodeAt(i);
        }

        return outputArray;
    };

    return {
        isSupported,
        permission,
        requestPermission,
        subscribe,
        unsubscribe,
    };
}

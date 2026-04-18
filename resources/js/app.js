import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

import { OhVueIcon, addIcons } from "oh-vue-icons";
import { 
    MdSpacedashboardOutlined,
    MdWorkoutline,
    MdPeopleoutline,
    MdAttachmoneyOutlined,
    MdSettingsOutlined,
    MdLogout,
    MdCheckcircleoutline,
    MdCancelOutlined,
    MdFileuploadOutlined,
    MdAdd,
    MdClose,
    MdLocationonOutlined,
    MdWarningamberOutlined,
    MdAttachfileOutlined,
    MdAttachfile,
    MdDeleteoutline,
    MdDownload,
    MdKeyboardarrowright,
    MdPersonaddOutlined,
    MdBadgeOutlined,
    MdContactphoneOutlined,
    MdHomeworkOutlined,
    RiLandscapeLine,
    RiCheckboxCircleLine,
    RiInformationLine,
    RiErrorWarningLine,
    RiCoinLine,
    MdEditOutlined,
    MdMapOutlined,
    MdLogin,
    MdCalculateOutlined,
    MdEdit,
} from "oh-vue-icons/icons";

addIcons(
    MdSpacedashboardOutlined,
    MdWorkoutline,
    MdPeopleoutline,
    MdAttachmoneyOutlined,
    MdSettingsOutlined,
    MdLogout,
    MdCheckcircleoutline,
    MdCancelOutlined,
    MdFileuploadOutlined,
    MdAdd,
    MdClose,
    MdLocationonOutlined,
    MdWarningamberOutlined,
    MdAttachfileOutlined,
    MdAttachfile,
    MdDeleteoutline,
    MdDownload,
    MdKeyboardarrowright,
    MdPersonaddOutlined,
    MdBadgeOutlined,
    MdContactphoneOutlined,
    MdHomeworkOutlined,
    RiLandscapeLine,
    RiCheckboxCircleLine,
    RiInformationLine,
    RiErrorWarningLine,
    RiCoinLine,
    MdEditOutlined,
    MdMapOutlined,
    MdLogin,
    MdCalculateOutlined,
    MdEdit,
);

const appName = import.meta.env.VITE_APP_NAME || 'Land Manager';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .component("v-icon", OhVueIcon)
            .mount(el);
    },
    progress: {
        color: '#ffffff',
    },
});

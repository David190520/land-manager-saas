<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    tenant: Object,
    users: Array,
});

const form = useForm({
    name: props.tenant?.name || '',
    company_name: props.tenant?.company_name || '',
    nit: props.tenant?.nit || '',
    phone: props.tenant?.phone || '',
    email: props.tenant?.email || '',
    address: props.tenant?.address || '',
    city: props.tenant?.city || '',
    department: props.tenant?.department || '',
});

// ── User management ───────────────────────────────────────────────────────────
const showNewUserForm = ref(false);
const editingUser = ref(null);

const newUserForm = useForm({
    name: '',
    email: '',
    password: '',
    role: 'secretary',
});

const editUserForm = useForm({
    name: '',
    role: 'secretary',
    is_active: true,
    password: '',
});

const submitNewUser = () => {
    newUserForm.post(route('users.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showNewUserForm.value = false;
            newUserForm.reset();
        },
    });
};

const openEditUser = (user) => {
    editingUser.value = user.id;
    editUserForm.name = user.name;
    editUserForm.role = user.role;
    editUserForm.is_active = user.is_active;
    editUserForm.password = '';
};

const cancelEditUser = () => {
    editingUser.value = null;
    editUserForm.reset();
};

const submitEditUser = (userId) => {
    editUserForm.put(route('users.update', userId), {
        preserveScroll: true,
        onSuccess: () => {
            editingUser.value = null;
        },
    });
};

const deleteUser = (userId) => {
    if (!confirm('¿Eliminar este usuario definitivamente?')) return;
    router.delete(route('users.destroy', userId), { preserveScroll: true });
};

const roleLabel = (role) => ({
    admin:      'Administrador',
    accountant: 'Contador',
    secretary:  'Secretaria',
}[role] || role);

const roleBadgeClass = (role) => ({
    admin:      'bg-white/10 border-white/20 text-white',
    accountant: 'bg-blue-500/10 border-blue-500/20 text-blue-400',
    secretary:  'bg-purple-500/10 border-purple-500/20 text-purple-400',
}[role] || 'bg-[#262626] border-[#3f3f46] text-[#a1a1aa]');
</script>

<template>
    <AppLayout>
        <Head title="Parámetros" />

        <div class="mb-8 animate-fade-in">
            <h1 class="text-2xl font-semibold text-white tracking-tight">Parámetros Maestros</h1>
            <p class="text-xs text-[#71717a] mt-1 font-medium tracking-wide">Configuración estructural del inquilino u organización.</p>
        </div>

        <div class="w-full grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 animate-slide-up">
            <!-- Left Column -->
            <div class="space-y-8 lg:space-y-12">
                <!-- Company Info -->
                <div class="bg-[#18181a] border border-[#2a2a2a] p-8 rounded-2xl hover:border-[#3f3f46] transition-colors">
                    <h3 class="text-[10px] font-bold text-white mb-6 uppercase tracking-widest flex items-center gap-3 pb-3 border-b border-[#2a2a2a]">
                        <v-icon name="md-workoutline" scale="1.1" fill="#71717a" />
                        Identidad Corporativa
                    </h3>

                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="label-dark text-[10px] uppercase tracking-widest font-bold">Razón Social</label>
                                <input v-model="form.company_name" type="text" class="input-dark bg-[#121212]" readonly />
                            </div>
                            <div>
                                <label class="label-dark text-[10px] uppercase tracking-widest font-bold">NIT / RUT</label>
                                <input v-model="form.nit" type="text" class="input-dark bg-[#121212]" readonly />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="label-dark text-[10px] uppercase tracking-widest font-bold">Línea Contacto</label>
                                <input v-model="form.phone" type="text" class="input-dark bg-[#121212]" readonly />
                            </div>
                            <div>
                                <label class="label-dark text-[10px] uppercase tracking-widest font-bold">Canal Web</label>
                                <input v-model="form.email" type="email" class="input-dark bg-[#121212]" readonly />
                            </div>
                        </div>
                        <div>
                            <label class="label-dark text-[10px] uppercase tracking-widest font-bold">Matriz Principal (Dirección)</label>
                            <input v-model="form.address" type="text" class="input-dark bg-[#121212]" readonly />
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="label-dark text-[10px] uppercase tracking-widest font-bold">Distrito</label>
                                <input v-model="form.city" type="text" class="input-dark bg-[#121212]" readonly />
                            </div>
                            <div>
                                <label class="label-dark text-[10px] uppercase tracking-widest font-bold">Región Territorial</label>
                                <input v-model="form.department" type="text" class="input-dark bg-[#121212]" readonly />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Info -->
                <div class="bg-[#18181a] border border-[#2a2a2a] p-8 rounded-2xl hover:border-[#3f3f46] transition-colors">
                    <h3 class="text-[10px] font-bold text-white mb-6 uppercase tracking-widest flex items-center gap-3 pb-3 border-b border-[#2a2a2a]">
                        <v-icon name="md-settings-outlined" scale="1.1" fill="#71717a" />
                        Entorno Analítico
                    </h3>
                    <div class="space-y-4 text-xs font-semibold uppercase tracking-wider text-white">
                        <div class="flex justify-between py-3 border-b border-[#2a2a2a]">
                            <span class="text-[#71717a]">Licencia</span>
                            <span class="px-2 py-1 rounded border border-[#3f3f46] bg-[#262626] text-white tracking-widest">
                                Enterprise (Ilimitada)
                            </span>
                        </div>
                        <div class="flex justify-between py-3 border-b border-[#2a2a2a]">
                            <span class="text-[#71717a]">Topología Crédito</span>
                            <span class="text-[#ededed]">Amortización Lineal Tasa Fija (0%)</span>
                        </div>
                        <div class="flex justify-between py-3 border-b border-[#2a2a2a]">
                            <span class="text-[#71717a]">Depuración Padrón</span>
                            <span class="text-[#a1a1aa]">Expulsión Automática en Mora > 10d</span>
                        </div>
                        <div class="flex justify-between py-3">
                            <span class="text-[#71717a]">Core App</span>
                            <span class="text-[#ededed] font-medium tracking-normal text-sm">v1.2.0-rc</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-8 lg:space-y-12">
                <!-- ACL reference -->
                <div class="bg-[#18181a] border border-[#2a2a2a] p-8 rounded-2xl hover:border-[#3f3f46] transition-colors">
                    <h3 class="text-[10px] font-bold text-white mb-6 uppercase tracking-widest flex items-center gap-3 pb-3 border-b border-[#2a2a2a]">
                        <v-icon name="md-peopleoutline" scale="1.1" fill="#71717a" />
                        Jerarquía Acceso (ACL)
                    </h3>
                    <div class="space-y-3">
                        <div class="bg-[#121212] border border-[#2a2a2a] rounded-xl p-4">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="px-2 py-0.5 rounded text-[9px] uppercase font-bold tracking-widest border border-white text-black bg-white">Admin</span>
                                <p class="text-white font-bold text-xs tracking-wide">Administrador</p>
                            </div>
                            <p class="text-[11px] text-[#71717a] leading-relaxed">Acceso total. Auditoría irrestricta, aprobación de ventas, asientos crediticios, parámetros y gestión de usuarios.</p>
                        </div>
                        <div class="bg-[#121212] border border-[#2a2a2a] rounded-xl p-4">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="px-2 py-0.5 rounded text-[9px] uppercase font-bold tracking-widest border border-blue-500/40 text-blue-400 bg-blue-500/10">Contador</span>
                                <p class="text-white font-bold text-xs tracking-wide">Accountant</p>
                            </div>
                            <p class="text-[11px] text-[#71717a] leading-relaxed">Lectura en Clientes y Proyectos. Acceso completo a Finanzas y Reportes. Sin acceso a Configuración ni creación de reservas.</p>
                        </div>
                        <div class="bg-[#121212] border border-[#2a2a2a] rounded-xl p-4">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="px-2 py-0.5 rounded text-[9px] uppercase font-bold tracking-widest border border-purple-500/40 text-purple-400 bg-purple-500/10">Secretaria</span>
                                <p class="text-white font-bold text-xs tracking-wide">Secretary</p>
                            </div>
                            <p class="text-[11px] text-[#71717a] leading-relaxed">Crea y edita clientes, crea reservas. Sin acceso a Finanzas ni Configuración.</p>
                        </div>
                    </div>
                </div>

                <!-- User Management -->
                <div class="bg-[#18181a] border border-[#2a2a2a] p-8 rounded-2xl hover:border-[#3f3f46] transition-colors">
                    <div class="flex items-center justify-between pb-4 border-b border-[#2a2a2a] mb-6">
                        <h3 class="text-[10px] font-bold text-white uppercase tracking-widest flex items-center gap-3">
                            <v-icon name="md-managedaccounts-outlined" scale="1.1" fill="#71717a" />
                            Gestión de Usuarios
                        </h3>
                        <button
                            @click="showNewUserForm = !showNewUserForm"
                            class="text-[10px] font-bold uppercase tracking-widest px-3 py-1.5 rounded border border-[#3f3f46] bg-[#262626] text-white hover:bg-[#3f3f46] transition-colors flex items-center gap-1.5"
                        >
                            <v-icon :name="showNewUserForm ? 'md-close' : 'md-add'" scale="0.8" />
                            {{ showNewUserForm ? 'Cancelar' : 'Nuevo Usuario' }}
                        </button>
                    </div>

                    <!-- New user form -->
                    <div v-if="showNewUserForm" class="bg-[#121212] border border-[#2a2a2a] rounded-xl p-5 mb-6">
                        <p class="text-[10px] font-bold text-[#71717a] uppercase tracking-widest mb-4">Nuevo Acceso</p>
                        <form @submit.prevent="submitNewUser" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="label-dark">Nombre Completo</label>
                                    <input v-model="newUserForm.name" type="text" class="input-dark bg-[#18181a]" placeholder="Nombre" />
                                    <p v-if="newUserForm.errors.name" class="text-red-400 text-xs mt-1">{{ newUserForm.errors.name }}</p>
                                </div>
                                <div>
                                    <label class="label-dark">Correo Electrónico</label>
                                    <input v-model="newUserForm.email" type="email" class="input-dark bg-[#18181a]" placeholder="correo@empresa.com" />
                                    <p v-if="newUserForm.errors.email" class="text-red-400 text-xs mt-1">{{ newUserForm.errors.email }}</p>
                                </div>
                                <div>
                                    <label class="label-dark">Contraseña</label>
                                    <input v-model="newUserForm.password" type="password" class="input-dark bg-[#18181a]" placeholder="Mínimo 8 caracteres" />
                                    <p v-if="newUserForm.errors.password" class="text-red-400 text-xs mt-1">{{ newUserForm.errors.password }}</p>
                                </div>
                                <div>
                                    <label class="label-dark">Rol</label>
                                    <select v-model="newUserForm.role" class="input-dark bg-[#18181a]">
                                        <option value="admin">Administrador</option>
                                        <option value="accountant">Contador</option>
                                        <option value="secretary">Secretaria</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex justify-end pt-2">
                                <button type="submit" class="btn-primary" :disabled="newUserForm.processing">
                                    {{ newUserForm.processing ? 'Creando...' : 'Crear Usuario' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- User list -->
                    <div class="space-y-3">
                        <div v-for="user in users" :key="user.id" class="bg-[#121212] border border-[#2a2a2a] rounded-xl overflow-hidden">
                            <!-- View row -->
                            <div v-if="editingUser !== user.id" class="flex items-center gap-4 p-4">
                                <div class="w-9 h-9 rounded-full bg-[#262626] flex items-center justify-center text-white font-semibold text-sm flex-shrink-0 border border-[#3f3f46]">
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-white truncate">{{ user.name }}</p>
                                    <p class="text-[10px] text-[#71717a] truncate">{{ user.email }}</p>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <span :class="['text-[9px] font-bold uppercase tracking-widest px-2 py-0.5 rounded border', roleBadgeClass(user.role)]">
                                        {{ roleLabel(user.role) }}
                                    </span>
                                    <span v-if="!user.is_active" class="text-[9px] font-bold uppercase tracking-widest px-2 py-0.5 rounded border border-rose-500/30 bg-rose-500/10 text-rose-400">
                                        Inactivo
                                    </span>
                                    <button @click="openEditUser(user)" class="p-1.5 text-[#71717a] hover:text-white transition-colors rounded border border-transparent hover:border-[#3f3f46]" title="Editar">
                                        <v-icon name="md-edit-outlined" scale="0.85" />
                                    </button>
                                    <button @click="deleteUser(user.id)" class="p-1.5 text-[#71717a] hover:text-rose-400 transition-colors rounded border border-transparent hover:border-rose-500/30" title="Eliminar">
                                        <v-icon name="md-delete-outlined" scale="0.85" />
                                    </button>
                                </div>
                            </div>

                            <!-- Edit row -->
                            <div v-else class="p-4 bg-[#1a1a1c]">
                                <p class="text-[10px] font-bold text-[#71717a] uppercase tracking-widest mb-3">Editando: {{ user.email }}</p>
                                <form @submit.prevent="submitEditUser(user.id)" class="space-y-3">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="label-dark">Nombre</label>
                                            <input v-model="editUserForm.name" type="text" class="input-dark bg-[#121212]" />
                                            <p v-if="editUserForm.errors.name" class="text-red-400 text-xs mt-1">{{ editUserForm.errors.name }}</p>
                                        </div>
                                        <div>
                                            <label class="label-dark">Rol</label>
                                            <select v-model="editUserForm.role" class="input-dark bg-[#121212]">
                                                <option value="admin">Administrador</option>
                                                <option value="accountant">Contador</option>
                                                <option value="secretary">Secretaria</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="label-dark">Nueva Contraseña (opcional)</label>
                                            <input v-model="editUserForm.password" type="password" class="input-dark bg-[#121212]" placeholder="Dejar en blanco para no cambiar" />
                                        </div>
                                        <div class="flex items-end pb-1">
                                            <label class="flex items-center gap-3 cursor-pointer">
                                                <input type="checkbox" v-model="editUserForm.is_active" class="w-4 h-4 rounded border-[#3f3f46] bg-[#18181a] text-white" />
                                                <span class="text-xs text-[#a1a1aa]">Cuenta activa</span>
                                            </label>
                                        </div>
                                    </div>
                                    <p v-if="editUserForm.errors.user" class="text-red-400 text-xs">{{ editUserForm.errors.user }}</p>
                                    <div class="flex justify-end gap-2 pt-1">
                                        <button type="button" @click="cancelEditUser" class="btn-secondary text-xs">Cancelar</button>
                                        <button type="submit" class="btn-primary text-xs" :disabled="editUserForm.processing">
                                            {{ editUserForm.processing ? 'Guardando...' : 'Guardar Cambios' }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <p v-if="!users || users.length === 0" class="text-xs text-[#71717a] text-center py-4">Sin usuarios registrados.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

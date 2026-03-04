<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    projects: Array,
});

const showCreateModal = ref(false);
const form = useForm({
    name: '',
    description: '',
    location: '',
    municipality: '',
    department: '',
    total_area: '',
    price_per_m2: '',
});

const submit = () => {
    form.post(route('projects.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Proyectos" />

        <!-- Header -->
        <div class="flex items-center justify-between mb-8 animate-fade-in">
            <div>
                <h1 class="text-2xl font-semibold text-white tracking-tight">Proyectos !</h1>
                <p class="text-sm text-[#71717a] mt-1">Gestión de proyectos inmobiliarios</p>
            </div>
            <button @click="showCreateModal = true" class="btn-primary">
                <v-icon name="md-add" scale="1" fill="black" class="mr-2" />
                Nuevo
            </button>
        </div>

        <!-- Projects Grid -->
        <div v-if="projects.length === 0" class="text-center py-20 animate-fade-in border border-[#2a2a2a] rounded-2xl bg-[#18181a]">
            <div class="w-16 h-16 mx-auto mb-6 rounded-2xl bg-[#1e1e1e] flex items-center justify-center border border-[#3f3f46]">
                <v-icon name="md-workoutline" scale="1.5" fill="#a1a1aa" />
            </div>
            <h3 class="text-lg font-semibold text-white mb-2">No hay proyectos aún</h3>
            <p class="text-sm text-[#71717a] mb-6">Crea tu primer proyecto para comenzar</p>
            <button @click="showCreateModal = true" class="btn-primary">
                Crear Proyecto
            </button>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <Link
                v-for="(project, index) in projects"
                :key="project.id"
                :href="route('projects.show', project.id)"
                class="block bg-[#18181a] border border-[#2a2a2a] p-5 rounded-2xl animate-slide-up hover:border-[#3f3f46] hover:bg-[#1c1c1e] transition-colors"
                :style="{ animationDelay: `${index * 50}ms` }"
            >
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-[#262626] flex items-center justify-center border border-[#3f3f46]">
                            <v-icon name="md-workoutline" scale="1.2" fill="#ededed" />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-white tracking-wide">{{ project.name }}</h3>
                            <p class="text-[10px] text-[#71717a] flex items-center gap-1 mt-0.5">
                                <v-icon name="md-locationon-outlined" scale="0.7" fill="#71717a" />
                                {{ project.location }}
                            </p>
                        </div>
                    </div>
                    <span :class="[
                        'px-2 py-0.5 rounded text-[10px] font-medium border',
                        project.status === 'active' ? 'bg-[#262626] text-white border-[#3f3f46]' :
                        project.status === 'paused' ? 'bg-[#1e1e1e] text-[#a1a1aa] border-[#2a2a2a]' :
                        'bg-[#121212] text-[#71717a] border-[#2a2a2a]'
                    ]">
                        {{ project.status === 'active' ? 'Activo' : project.status === 'paused' ? 'Pausado' : 'Completado' }}
                    </span>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-2 mb-4">
                    <div class="text-center p-2 rounded-lg bg-[#141414] border border-[#2a2a2a]">
                        <p class="text-sm font-bold text-white">{{ project.available_lots }}</p>
                        <p class="text-[9px] text-[#71717a] uppercase mt-0.5 tracking-wider">Disp.</p>
                    </div>
                    <div class="text-center p-2 rounded-lg bg-[#141414] border border-[#2a2a2a]">
                        <p class="text-sm font-bold text-[#a1a1aa]">{{ project.reserved_lots }}</p>
                        <p class="text-[9px] text-[#71717a] uppercase mt-0.5 tracking-wider">Reser.</p>
                    </div>
                    <div class="text-center p-2 rounded-lg bg-[#141414] border border-[#2a2a2a]">
                        <p class="text-sm font-bold text-[#a1a1aa]">{{ project.sold_lots }}</p>
                        <p class="text-[9px] text-[#71717a] uppercase mt-0.5 tracking-wider">Vend.</p>
                    </div>
                </div>

                <!-- Info -->
                <div class="flex items-center justify-between text-[10px] text-[#71717a] bg-[#1e1e1e] px-3 py-2 rounded-lg border border-[#2a2a2a]">
                    <span>{{ project.blocks_count }} mz • {{ project.lots_count }} lotes</span>
                    <span v-if="project.total_area">{{ project.total_area.toLocaleString() }} m²</span>
                </div>
            </Link>
        </div>

        <!-- Create Project Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-[#000000] opacity-80 backdrop-blur-sm" @click="showCreateModal = false"></div>
                    <div class="relative w-full max-w-lg bg-[#18181a] border border-[#2a2a2a] rounded-2xl p-8 animate-slide-up shadow-2xl">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-lg font-semibold text-white tracking-tight">Nuevo Proyecto</h2>
                            <button @click="showCreateModal = false" class="text-[#71717a] hover:text-white">
                                <v-icon name="md-close" />
                            </button>
                        </div>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <label class="label-dark">Nombre del Proyecto</label>
                                <input v-model="form.name" type="text" class="input-dark bg-[#141414]" placeholder="Ej: Proyecto Alfa" />
                                <p v-if="form.errors.name" class="text-[#ef4444] text-[10px] mt-1">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="label-dark">Descripción</label>
                                <textarea v-model="form.description" class="input-dark bg-[#141414]" rows="2" placeholder="Detalles generales..."></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="label-dark">Ubicación</label>
                                    <input v-model="form.location" type="text" class="input-dark bg-[#141414]" placeholder="Sector" />
                                    <p v-if="form.errors.location" class="text-[#ef4444] text-[10px] mt-1">{{ form.errors.location }}</p>
                                </div>
                                <div>
                                    <label class="label-dark">Municipio</label>
                                    <input v-model="form.municipality" type="text" class="input-dark bg-[#141414]" placeholder="Ciudad" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="label-dark">M² Totales</label>
                                    <input v-model="form.total_area" type="number" class="input-dark bg-[#141414]" placeholder="0" />
                                </div>
                                <div>
                                    <label class="label-dark">Precio base m²</label>
                                    <input v-model="form.price_per_m2" type="number" class="input-dark bg-[#141414]" placeholder="0" />
                                </div>
                            </div>
                            <div class="flex justify-end gap-3 pt-6 border-t border-[#2a2a2a] mt-6">
                                <button type="button" @click="showCreateModal = false" class="btn-secondary">Cancelar</button>
                                <button type="submit" class="btn-primary" :disabled="form.processing">
                                    {{ form.processing ? 'Creando...' : 'Crear ' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

import Dropzone from 'dropzone';

const onSending = (file, xhr, formData) => {
    console.log(file);
    console.log(formData);

    
    let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    console.log(token);

    // formData.append("_token", document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    xhr.setRequestHeader('X-CSRF-TOKEN', token/*csrfToken*/);
};

const onSuccess = (file, response) => {
    console.log('Upload successful:', response);
};

const csrfToken = ref(null);
const CHUNK_SIZE = 1024 * 1024 * 4; // 4MB

onMounted(() => {
    new Dropzone('#my-dropzone', {
        url: '/uploadFiles',
        chunking: true,
        chunkSize: CHUNK_SIZE,
        addRemoveLinks: true,
        parallelChunkUploads: false,
        retryChunks: true,
        retryChunksLimit: 3,
        init: function () {
            this.on("sending", onSending);
            this.on("success", onSuccess);
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        csrfToken.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken.value) {
        } else {
            console.error('CSRF token not found');
        }
    });
});

</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900">
                        You're logged in!
                    </div>
                </div>
            </div>
        </div>
        <div class="mx-auto max-w-7xl flex flex-row justify-center items-center">
            <div id="my-dropzone" class="dropzone"></div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@import 'dropzone/dist/dropzone.css';
</style>

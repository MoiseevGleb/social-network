<script setup>

import GroupList from "@/Components/App/GroupList.vue";
import FollowingList from "@/Components/App/FollowingList.vue";
import CreatePost from "@/Components/App/CreatePost.vue";
import PostList from "@/Components/App/PostList.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

defineProps({
    posts: Object,
    groups: Array,
})

</script>

<template>
    <AuthenticatedLayout>
        <div class="lg:grid lg:grid-cols-12 gap-3 h-full flex flex-col">
            <div class="lg:col-span-3 py-3 px-3 lg:order-1 overflow-hidden flex flex-col">
                <GroupList :groups="groups"/>
            </div>

            <div class="lg:col-span-3 py-3 px-3 lg:order-3 overflow-hidden flex flex-col">
                <FollowingList/>
            </div>

            <div class="lg:col-span-6 py-3 px-3 h-full flex-1 lg:order-2 overflow-y-auto flex flex-col">
                <CreatePost />
                <PostList :posts="posts.data"/>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>

</style>

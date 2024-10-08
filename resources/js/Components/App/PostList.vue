<script setup>
import PostItem from "@/Components/App/PostItem.vue";
import PostModal from "@/Components/App/PostModal.vue";
import {onMounted, ref} from "vue";
import {usePage} from "@inertiajs/vue3";
import AttachmentPreviewModal from "@/Components/App/AttachmentPreviewModal.vue";

const props = defineProps({
    posts: Array
})

const authUser = usePage().props.auth.user

const showEditModal = ref(false)
const showPreviewModal = ref(false)

const editPost = ref({})
const previewPost = ref({})

function openEditModal(post) {
    editPost.value = post
    showEditModal.value = true
}

function openPreviewModal(post, index) {
    previewPost.value = {post, index}
    showPreviewModal.value = true
}

function onModalHide() {
    editPost.value = {
        id: null,
        body: '',
        user: authUser
    }
}

const loadMoreIntersect = ref(null)

function loadMore() {
    if (allPosts.value.next) {
        axios.get(allPosts.value.next).then(({data}) => {
            allPosts.value.data = [...allPosts.value.data, ...data.data]
            allPosts.value.next = data.links.next
        })
    }
}

const page = usePage();

const allPosts = ref({
    data: page.props.posts.data,
    next: page.props.posts.links.next,
})

onMounted(() => {
    const observer = new IntersectionObserver((entries) =>
        entries.forEach(entry => entry.isIntersecting && loadMore())
    )

    observer.observe(loadMoreIntersect.value)
})

</script>

<template>
    <div class="flex flex-col gap-3 h-full overflow-y-auto">
        <PostItem
            v-for="post of allPosts.data" :key="post.id"
            :post="post"
            @editClick="openEditModal"
            @attachmentClick="openPreviewModal"
        />
        <div ref="loadMoreIntersect"></div>
    </div>

    <PostModal
        :post="editPost"
        v-model="showEditModal"
        @hide="onModalHide"
    />

    <AttachmentPreviewModal
        :attachments="previewPost.post?.attachments || []"
        v-model:index="previewPost.index"
        v-model="showPreviewModal"
        @hide="onModalHide"
    />
</template>

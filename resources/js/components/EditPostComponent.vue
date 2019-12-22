<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        Edit New Post
                    </div>
                    <div class="card-body">
                       <form @submit.prevent="addPosts">
                            <div class="form-group">
                                <label>Titel</label>
                                <input 
                                       type="text" 
                                       class="form-control" 
                                       v-model="posts.title">
                            </div>
                            <div class="form-group">
                                <label>Post Content</label>
                                <ckeditor 
                                          :editor="editor" 
                                          v-model="posts.description" 
                                          :config="editorConfig">
                                </ckeditor>
                            </div>
                            <button type="submit" class="btn btn-primary">Update a Post</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import axios from 'axios';

    export default {
        props: ['title', 'description','postId'],
        data: function(){
            return {
                
                posts: {
                    title: this.title,
                    description:this.description,
                },
                editor: ClassicEditor,
                editorConfig: {
                    toolbar:["undo", "redo", "bold", "italic", "blockQuote", "heading", "link", "numberedList", "bulletedList", "mediaEmbed", "insertTable"]
                }
            };
        },
        methods: {
            addPosts() {
                this.axios
                    .post('/post/'+this.postId+'/update', this.posts)
                    .then(response => (
                        window.location.href = '/post'
                    ))
                    .catch(error => console.log(error))
                    .finally(() => this.loading = false)
            }
        }
    }
</script>

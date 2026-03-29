<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Youtube from '@tiptap/extension-youtube'
import Placeholder from '@tiptap/extension-placeholder'
import { watch, onBeforeUnmount } from 'vue'

import { Bold, Italic, List, Link as LinkIcon, Youtube as YoutubeIcon, Heading2 } from 'lucide-vue-next'
import { Button } from '@/Components/ui/button'

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Write your post description here...',
  }
})

const emit = defineEmits(['update:modelValue'])

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit,
    Placeholder.configure({
      placeholder: props.placeholder,
    }),
    Link.configure({
      openOnClick: false, 
      HTMLAttributes: {
        class: 'text-blue-600 underline cursor-pointer',
      }
    }),
    Youtube.configure({
      controls: true,
      nocookie: true,
      HTMLAttributes: {
        class: 'w-full aspect-video rounded-md my-4 shadow-sm',
      }
    }),
  ],
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  },
  editorProps: {
    attributes: {
      // FIX: Increased min-height to 200px and text size to text-sm/text-base so it's readable
      class: 'focus:outline-none min-h-[200px] p-4 text-sm sm:text-base text-slate-800 dark:text-slate-200 leading-relaxed',
    },
  },
})

watch(() => props.modelValue, (value) => {
  const isSame = editor.value?.getHTML() === value
  if (isSame) return
  editor.value?.commands.setContent(value, false)
})

onBeforeUnmount(() => {
  editor.value?.destroy()
})

const setLink = () => {
  const previousUrl = editor.value.getAttributes('link').href
  const url = window.prompt('URL', previousUrl)

  if (url === null) return 
  if (url === '') {
    editor.value.chain().focus().extendMarkRange('link').unsetLink().run()
    return
  }
  editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
}

const addVideo = () => {
  const url = prompt('Enter YouTube URL')
  if (url) {
    editor.value.commands.setYoutubeVideo({ src: url })
  }
}
</script>

<template>
  <div class="border border-slate-300 dark:border-slate-700 rounded-md overflow-hidden bg-white dark:bg-slate-900 flex flex-col focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-transparent transition-all shadow-sm">
    
    <div v-if="editor" class="flex flex-wrap items-center gap-1 border-b border-slate-200 dark:border-slate-700 p-1.5 bg-slate-50 dark:bg-slate-800/80">
      
      <Button type="button" variant="ghost" size="icon" class="h-8 w-8 rounded hover:bg-slate-200 dark:hover:bg-slate-700" :class="{ 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400': editor.isActive('bold') }" @click="editor.chain().focus().toggleBold().run()">
        <Bold class="w-4 h-4" />
      </Button>

      <Button type="button" variant="ghost" size="icon" class="h-8 w-8 rounded hover:bg-slate-200 dark:hover:bg-slate-700" :class="{ 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400': editor.isActive('italic') }" @click="editor.chain().focus().toggleItalic().run()">
        <Italic class="w-4 h-4" />
      </Button>

      <div class="w-px h-5 bg-slate-300 dark:bg-slate-600 mx-1"></div>

      <Button type="button" variant="ghost" size="icon" class="h-8 w-8 rounded hover:bg-slate-200 dark:hover:bg-slate-700" :class="{ 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400': editor.isActive('heading', { level: 2 }) }" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()">
        <Heading2 class="w-4 h-4" />
      </Button>

      <Button type="button" variant="ghost" size="icon" class="h-8 w-8 rounded hover:bg-slate-200 dark:hover:bg-slate-700" :class="{ 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400': editor.isActive('bulletList') }" @click="editor.chain().focus().toggleBulletList().run()">
        <List class="w-4 h-4" />
      </Button>

      <div class="w-px h-5 bg-slate-300 dark:bg-slate-600 mx-1"></div>

      <Button type="button" variant="ghost" size="icon" class="h-8 w-8 rounded hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-blue-900/30" :class="{ 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400': editor.isActive('link') }" @click="setLink">
        <LinkIcon class="w-4 h-4" />
      </Button>

      <Button type="button" variant="ghost" size="icon" class="h-8 w-8 rounded hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/30" @click="addVideo">
        <YoutubeIcon class="w-4 h-4" />
      </Button>

    </div>

    <EditorContent :editor="editor" class="flex-1 cursor-text ProseMirror-container" />
  </div>
</template>

<style>
.ProseMirror p.is-editor-empty:first-child::before {
  content: attr(data-placeholder);
  float: left;
  color: #94a3b8;
  pointer-events: none;
  height: 0;
}
.ProseMirror ul {
  list-style-type: disc;
  padding-left: 1.5rem;
  margin-top: 0.5rem;
  margin-bottom: 0.5rem;
}
.ProseMirror h2 {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  margin-top: 1rem;
}
.ProseMirror a {
  color: #2563eb;
  text-decoration: underline;
}
</style>
<template>
  <div>
    <h1>Task Manager</h1>
    <input v-model="newTask" placeholder="Add task" @keyup.enter.prevent="addTask" />
    <ul>
      <li v-for="task in tasks" :key="task.id">
        <input type="checkbox" v-model="task.completed" @change="toggleTask(task)" />
        <span :style="{ textDecoration: task.completed ? 'line-through' : 'none' }">
          {{ task.title }}
        </span>
        <button @click="deleteTask(task)">Delete</button>
      </li>
    </ul>
  </div>
  <div>
    <h1>v-model</h1>
    <input type="text" v-model="name">
    <p>My name is: {{ name }}</p>
  </div>
</template>
  
  <script setup>
  import { ref, onMounted } from 'vue'
  import axios from 'axios'
  
  // state
  const tasks = ref([])
  const newTask = ref('')
  const name = ref('')
  
  // lifecycle
  onMounted(() => {
    fetchTasks()
  })
  
  // methods
  function fetchTasks() {
    axios.get('/api/tasks').then(res => tasks.value = res.data)
  }
  
  function addTask() {
    if (!newTask.value) return
    axios.post('/api/tasks', { title: newTask.value })
      .then(res => {
        tasks.value.push(res.data)
        newTask.value = ''
      })
  }
  
  function toggleTask(task) {
    axios.put(`/api/tasks/${task.id}`, { completed: task.completed })
  }
  
  function deleteTask(task) {
    axios.delete(`/api/tasks/${task.id}`)
      .then(() => {
        tasks.value = tasks.value.filter(t => t.id !== task.id)
      })
  }
  </script>
  
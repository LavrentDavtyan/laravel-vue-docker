import axios from '@/http';

export const shareService = {
    async listTopics() {
        const { data } = await axios.get('share/topics');
        return data.data;
    },
    async createTopic(payload) {
        const { data } = await axios.post('share/topics', payload);
        return data.data;
    },
    async rotateInvite(topicId) {
        const { data } = await axios.post(`share/topics/${topicId}/invite/rotate`);
        return data.data;
    },
    async joinByToken(token) {
        const { data } = await axios.post(`share/join/${token}`);
        return data.data;
    },

    async listMembers(topicId) {
        const { data } = await axios.get(`share/topics/${topicId}/members`);
        return data.data; // { members, invite_token, is_owner, status }
    },
    async leaveTopic(topicId) {
        const { data } = await axios.post(`share/topics/${topicId}/leave`);
        return data;
    },

    async listExpenses(topicId) {
        const { data } = await axios.get(`share/topics/${topicId}/expenses`);
        return data.data; // { expenses, status? }
    },
    async createExpense(topicId, payload) {
        const { data } = await axios.post(`share/topics/${topicId}/expenses`, payload);
        return data.data;
    },
    async deleteExpense(topicId, expenseId) {
        const { data } = await axios.delete(`share/topics/${topicId}/expenses/${expenseId}`);
        return data.data; // { deleted: true }
    },

    async listBalances(topicId) {
        const { data } = await axios.get(`share/topics/${topicId}/balances`);
        return data.data; // { members, balances, transfers }
    },

    async closeTopic(topicId) {
        const { data } = await axios.post(`share/topics/${topicId}/close`);
        return data.data; // { topic_id, status: 'closed' }
    },
    async reopenTopic(topicId) { // <- keep name "reopenTopic" in service
        const { data } = await axios.post(`share/topics/${topicId}/open`);
        return data.data; // { topic_id, status: 'open' }
    },
};

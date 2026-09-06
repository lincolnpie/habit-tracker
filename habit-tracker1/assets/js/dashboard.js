function toggleModal(modalId, show) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = show ? 'flex' : 'none';
    }
}

function editHabit(habit) {
    document.getElementById('edit_habit_id').value = habit.id;
    document.getElementById('edit_title').value = habit.title;
    document.getElementById('edit_category_id').value = habit.category_id || '';
    document.getElementById('edit_frequency').value = habit.target_frequency;
    document.getElementById('edit_description').value = habit.description || '';
    
    toggleModal('editHabitModal', true);
}
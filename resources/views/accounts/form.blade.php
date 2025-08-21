<div class="tab-pane fade" id="users" role="tabpanel">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Usuários da Conta</h5>
            {{-- [MODIFICADO] Este botão agora abre o modal --}}
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bx bx-plus me-1"></i> Adicionar Usuário
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Papel na Conta</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    {{-- [NOVO] Adicionado ID ao tbody para fácil manipulação com JS --}}
                    <tbody id="account-users-table-body">
                        @forelse ($account->users as $user)
                        @include('accounts.partials.user-row', ['user' => $user, 'account' => $account])
                        @empty
                        <tr id="no-users-row">
                            <td colspan="4" class="text-center">Nenhum usuário associado a esta conta.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- [NOVO] HTML do Modal para Adicionar Usuário --}}
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Adicionar Novo Usuário à Conta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{-- Formulário com ID para o JS --}}
            <form id="formAddUserToAccount" action="{{ route('accounts.users.add', $account) }}" method="POST">
                <div class="modal-body">
                    @csrf
                    <div class="mb-3">
                        <label for="user_name" class="form-label">Nome do Usuário</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="user_email" class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="user_password" class="form-label">Senha</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="user_role" class="form-label">Papel na Conta</label>
                        <select name="role" class="form-select" required>
                            <option value="member">Membro (Member)</option>
                            <option value="admin">Administrador da Conta (Admin)</option>
                            <option value="owner">Dono (Owner)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar e Adicionar</button>
                </div>
            </form>
        </div>
    </div>
</div>
import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {CoreModule} from '../core/core.module';
import {LoginComponent} from './components/login';
import {RouterModule} from '@angular/router';
import {InlineSVGModule} from 'ng-inline-svg';
import {NgxSelectModule} from 'ngx-select-ex';
import {NgbModule} from '@ng-bootstrap/ng-bootstrap';
import {AngularEditorModule} from '@kolkov/angular-editor';
import {FormsModule} from '@angular/forms';
import {NgCircleProgressModule} from 'ng-circle-progress';
import {UserManagementRoutingModule} from './user-management-routing.module';
import { UserSettingsComponent } from './components/user-settings/user-settings.component';
import { CreateUserComponent } from './components/create-user/create-user.component';
import { BackofficeUsersComponent } from './pages/backoffice-users/backoffice-users.component';
import { BackofficeUserListComponent } from './components/backoffice-user-list/backoffice-user-list.component';

@NgModule({
    declarations: [
        LoginComponent,
        UserSettingsComponent,
        CreateUserComponent,
        BackofficeUsersComponent,
        BackofficeUserListComponent,
    ],
    imports: [
        CommonModule,
        CoreModule,
        UserManagementRoutingModule,
        RouterModule,
        InlineSVGModule.forRoot(),
        NgxSelectModule,
        NgbModule,
        AngularEditorModule,
        FormsModule,
        NgCircleProgressModule.forRoot({
            space: -5
        }),
    ],
    exports: [LoginComponent]
})
export class UserManagementModule {
}

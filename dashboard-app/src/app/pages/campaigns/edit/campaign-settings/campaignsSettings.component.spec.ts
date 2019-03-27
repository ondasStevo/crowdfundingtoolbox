import { ComponentFixture, TestBed } from '@angular/core/testing';
import { NO_ERRORS_SCHEMA } from '@angular/core';
import { KeyValueDiffers } from '@angular/core';
import { CampaignService } from '../../../../_services/campaign.service';
import { ActivatedRoute } from '@angular/router';
import { paymentTypes } from '../../../../_models/enums';
import { CampaignsSettingsComponent } from './campaignsSettings.component';
describe('CampaignsSettingsComponent', () => {
  let component: CampaignsSettingsComponent;
  let fixture: ComponentFixture<CampaignsSettingsComponent>;
  beforeEach(() => {
    const keyValueDiffersStub = { find: () => ({ create: () => ({}) }) };
    const campaignServiceStub = {};
    const activatedRouteStub = {};
    TestBed.configureTestingModule({
      schemas: [NO_ERRORS_SCHEMA],
      declarations: [CampaignsSettingsComponent],
      providers: [
        { provide: KeyValueDiffers, useValue: keyValueDiffersStub },
        { provide: CampaignService, useValue: campaignServiceStub },
        { provide: ActivatedRoute, useValue: activatedRouteStub }
      ]
    });
    fixture = TestBed.createComponent(CampaignsSettingsComponent);
    component = fixture.componentInstance;
  });
  it('can load instance', () => {
    expect(component).toBeTruthy();
  });
  it('campaignNameLength defaults to: 0', () => {
    expect(component.campaignNameLength).toEqual(0);
  });
  it('opened defaults to: 1', () => {
    expect(component.opened).toEqual(1);
  });
  it('supportSettings defaults to: General', () => {
    expect(component.supportSettings).toEqual('General');
  });
  it('paymentTypes defaults to: paymentTypes', () => {
    expect(component.paymentTypes).toEqual(paymentTypes);
  });
});
